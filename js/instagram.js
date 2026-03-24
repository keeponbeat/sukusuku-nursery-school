document.addEventListener('DOMContentLoaded', () => {
  const feedContainer = document.getElementById('instafeed');
  if (!feedContainer || typeof IG_ACCESS_TOKEN === 'undefined') return;

  const limit = 6;
  const url = `https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp&access_token=${IG_ACCESS_TOKEN}`;

  fetch(url)
    .then(res => res.json())
    .then(data => {
      if (data.data) {
        const posts = data.data.slice(0, limit);
        let html = '';
        posts.forEach(post => {
          // Use thumbnail for videos, standard image URL for photos
          const imgUrl = (post.media_type === 'VIDEO') ? post.thumbnail_url : post.media_url;
          // create a safe alt text
          const altText = post.caption ? post.caption.replace(/"/g, '&quot;').slice(0, 50) : 'Instagram Post';
          const hoverText = post.caption ? post.caption.replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;').slice(0, 80) + (post.caption.length > 80 ? '...' : '') : '写真を見る';
          
          html += `
            <a href="${post.permalink}" target="_blank" class="insta-item">
              <div class="insta-img-wrapper">
                <img src="${imgUrl}" alt="${altText}">
                <div class="insta-hover">
                  <p class="insta-caption">${hoverText}</p>
                </div>
              </div>
            </a>
          `;
        });
        feedContainer.innerHTML = html;
      } else {
        feedContainer.innerHTML = '<p style="text-align: center; color: #666; width: 100%; grid-column: 1 / -1;">Instagramの投稿を取得できませんでした。</p>';
        if (data.error) console.error('Instagram API Error Details:', data.error);
      }
    })
    .catch(err => {
      console.error('Instagram API Fetch Error:', err);
      feedContainer.innerHTML = '<p style="text-align: center; color: #666; width: 100%; grid-column: 1 / -1;">Instagramの読み込みに失敗しました。</p>';
    });
});
