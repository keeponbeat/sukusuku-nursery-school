const fs = require('fs');

const TOKEN = process.env.IG_ACCESS_TOKEN;
const APP_ID = process.env.IG_APP_ID;
const APP_SECRET = process.env.IG_APP_SECRET;
const BUSINESS_ID = process.env.IG_BUSINESS_ID;

if (!TOKEN || !APP_ID || !APP_SECRET || !BUSINESS_ID) {
  console.error('Error: Required environment variables (IG_ACCESS_TOKEN, IG_APP_ID, IG_APP_SECRET, IG_BUSINESS_ID) are missing.');
  process.exit(1);
}

const LIMIT = 6;
const FEED_PATH = 'js/insta_feed.json';
const API_VERSION = 'v25.0';

async function run() {
  try {
    let currentToken = TOKEN;
    let lastRefresh = 0;

    // 1. 既存のJSONファイルを読み込んで前回のリフレッシュ時間を取得
    if (fs.existsSync(FEED_PATH)) {
      try {
        const existingData = JSON.parse(fs.readFileSync(FEED_PATH, 'utf8'));
        lastRefresh = existingData.last_refresh || 0;
      } catch (e) {
        console.warn('Could not read existing insta_feed.json, starting fresh.');
      }
    }

    const now = Date.now();
    // 2. 前回のリフレッシュから30日以上経過していればリフレッシュ（長期トークンへの交換・延長）を試みる
    if (now - lastRefresh > 2592000000) {
      console.log('Attempting to refresh/exchange token...');
      const refreshUrl = `https://graph.facebook.com/${API_VERSION}/oauth/access_token?grant_type=fb_exchange_token&client_id=${APP_ID}&client_secret=${APP_SECRET}&fb_exchange_token=${currentToken}`;
      const refreshRes = await fetch(refreshUrl);
      const refreshData = await refreshRes.json();
      
      if (refreshData.access_token) {
        console.log('Successfully refreshed/exchanged token.');
        currentToken = refreshData.access_token;
        lastRefresh = now;
      } else {
        console.warn('Token refresh/exchange returned no new token. It might be already long-lived or invalid:', refreshData);
      }
    }

    // 3. 最新の投稿データを取得 (Instagram Graph API 方式)
    console.log('Fetching Instagram Media from Business Account...');
    const url = `https://graph.facebook.com/${API_VERSION}/${BUSINESS_ID}/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,children{media_url,media_type,thumbnail_url}&access_token=${currentToken}`;
    const res = await fetch(url);
    const data = await res.json();

    if (data.data) {
      const posts = data.data.slice(0, LIMIT).map(post => {
        // CAROUSEL_ALBUM（スライド投稿）の場合、childrenの1枚目の画像をメイン画像として採用する
        // これにより、モバイルブラウザでの表示互換性を高める
        if (post.media_type === 'CAROUSEL_ALBUM' && post.children && post.children.data && post.children.data.length > 0) {
          const firstChild = post.children.data[0];
          const bestUrl = (firstChild.media_type === 'VIDEO') ? firstChild.thumbnail_url : firstChild.media_url;
          return {
            ...post,
            media_url: bestUrl || post.media_url
          };
        }
        return post;
      });
      // 投稿データとともに、前回のリフレッシュ時間も一緒にJSONへ保存
      fs.writeFileSync(FEED_PATH, JSON.stringify({ data: posts, last_refresh: lastRefresh }, null, 2));
      console.log('Successfully saved to js/insta_feed.json');
    } else {
      console.error('No data found from Instagram API:', data);
      if (data.error) {
        console.error('Error Details:', JSON.stringify(data.error, null, 2));
      }
      process.exit(1);
    }

  } catch (error) {
    console.error('Failed to execute script:', error);
    process.exit(1);
  }
}

run();
