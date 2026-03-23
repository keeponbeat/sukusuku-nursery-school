document.addEventListener('DOMContentLoaded', () => {
  // Random Hero Image
  const heroImg = document.getElementById('hero-image-main');
  if (heroImg) {
    const randomNum = Math.floor(Math.random() * 3) + 1; // 1, 2, or 3
    heroImg.src = `images/hero-${randomNum}.png`;
  }

  // Hamburger Menu
  const hamburger = document.querySelector('.hamburger');
  const navOverlay = document.querySelector('.mobile-nav');

  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navOverlay.classList.toggle('open');
    document.body.classList.toggle('no-scroll');
  });

  // Close mobile nav when clicking a link
  const navLinks = document.querySelectorAll('.mobile-nav a');
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('active');
      navOverlay.classList.remove('open');
      document.body.classList.remove('no-scroll');
    });
  });

  // FAQ Accordion
  const faqItems = document.querySelectorAll('.faq-item');
  
  faqItems.forEach(item => {
    const question = item.querySelector('.faq-q');
    
    question.addEventListener('click', () => {
      // Toggle active state
      item.classList.toggle('active');
      
      // Close others if desired (optional)
      faqItems.forEach(otherItem => {
        if (otherItem !== item && otherItem.classList.contains('active')) {
          otherItem.classList.remove('active');
        }
      });
    });
  });

  // Intersection Observer for fade-in animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.animationPlayState = 'running';
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  // Pause animations initially so they only run when scrolled into view
  document.querySelectorAll('.fade-in').forEach(element => {
    element.style.animationPlayState = 'paused';
    observer.observe(element);
  });

});
