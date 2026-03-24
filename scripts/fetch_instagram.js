const fs = require('fs');

const TOKEN = process.env.IG_ACCESS_TOKEN;
if (!TOKEN) {
  console.error('Error: IG_ACCESS_TOKEN environment variable is missing.');
  process.exit(1);
}

const LIMIT = 6;
const FEED_PATH = 'js/insta_feed.json';

async function run() {
  try {
    // 1. 既存のJSONファイルを読み込んで前回のリフレッシュ時間を取得
    let lastRefresh = 0;
    if (fs.existsSync(FEED_PATH)) {
      try {
        const existingData = JSON.parse(fs.readFileSync(FEED_PATH, 'utf8'));
        lastRefresh = existingData.last_refresh || 0;
      } catch (e) {
        console.warn('Could not read existing insta_feed.json, starting fresh.');
      }
    }

    const now = Date.now();
    // 2. 前回のリフレッシュから30日以上（約25億9200万ミリ秒）経過していればリフレッシュAPIを叩く
    // ※有効期限60日のうち、残り30日を切ったタイミングで延長させる安全設計
    if (now - lastRefresh > 2592000000) {
      console.log('Token is older than 30 days. Attempting to refresh...');
      const refreshUrl = `https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=${TOKEN}`;
      const refreshRes = await fetch(refreshUrl);
      const refreshData = await refreshRes.json();
      
      if (refreshData.access_token) {
        console.log('Successfully requested token refresh from Meta. Lifetime extended to 60 days.');
        lastRefresh = now; // 成功したらタイムスタンプを更新
      } else {
        console.warn('Failed to refresh token, or token already refreshed recently:', refreshData);
      }
    }

    // 3. 最新の投稿データを取得
    console.log('Fetching Instagram Media...');
    const url = `https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp&access_token=${TOKEN}`;
    const res = await fetch(url);
    const data = await res.json();

    if (data.data) {
      const posts = data.data.slice(0, LIMIT);
      // 投稿データとともに、前回のリフレッシュ時間も一緒にJSONへ保存（状態の永続化）
      fs.writeFileSync(FEED_PATH, JSON.stringify({ data: posts, last_refresh: lastRefresh }, null, 2));
      console.log('Successfully saved to js/insta_feed.json');
    } else {
      console.error('No data found from Instagram API:', data);
      process.exit(1);
    }

  } catch (error) {
    console.error('Failed to execute script:', error);
    process.exit(1);
  }
}

run();
