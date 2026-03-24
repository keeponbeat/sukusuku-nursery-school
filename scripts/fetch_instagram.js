const fs = require('fs');
const https = require('https');

const TOKEN = process.env.IG_ACCESS_TOKEN;
if (!TOKEN) {
  console.error('Error: IG_ACCESS_TOKEN environment variable is missing.');
  process.exit(1);
}

const LIMIT = 6;
const URL = `https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp&access_token=${TOKEN}`;

console.log('Fetching Instagram API...');

https.get(URL, (res) => {
  let data = '';
  
  res.on('data', (chunk) => {
    data += chunk;
  });
  
  res.on('end', () => {
    try {
      const parsed = JSON.parse(data);
      if (parsed.data) {
        const posts = parsed.data.slice(0, LIMIT);
        // 保存先を js/insta_feed.json に指定
        fs.writeFileSync('js/insta_feed.json', JSON.stringify({ data: posts }, null, 2));
        console.log('Successfully saved to js/insta_feed.json');
      } else {
        console.error('No data found from Instagram API:', parsed);
        process.exit(1);
      }
    } catch (e) {
      console.error('Error parsing JSON:', e);
      process.exit(1);
    }
  });
}).on('error', (err) => {
  console.error('Failed to fetch from Instagram API:', err.message);
  process.exit(1);
});
