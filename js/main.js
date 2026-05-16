document.addEventListener('DOMContentLoaded', () => {
  // Random Hero Image
  const heroImg = document.getElementById('hero-image-main');
  if (heroImg) {
    const randomNum = Math.floor(Math.random() * 3) + 1; // 1, 2, or 3
    heroImg.src = `images/hero-${randomNum}.png`;
    
    // Add specific class for exterior image to adjust positioning
    if (randomNum === 3) {
      heroImg.classList.add('hero-img-exterior');
    } else {
      heroImg.classList.remove('hero-img-exterior');
    }
  }

  // Hamburger Menu
  const hamburger = document.querySelector('.hamburger');
  const navOverlay = document.querySelector('.mobile-nav');

  if (hamburger && navOverlay) {
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('active');
      navOverlay.classList.toggle('open');
      document.body.classList.toggle('no-scroll');
    });
  }

  // Close mobile nav when clicking a link
  const navLinks = document.querySelectorAll('.mobile-nav a');
  if (navLinks.length > 0) {
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (hamburger) hamburger.classList.remove('active');
        if (navOverlay) navOverlay.classList.remove('open');
        document.body.classList.remove('no-scroll');
      });
    });
  }

  // FAQ Accordion
  const faqItems = document.querySelectorAll('.faq-item');
  if (faqItems.length > 0) {
    faqItems.forEach(item => {
      const question = item.querySelector('.faq-q');
      if (question) {
        question.addEventListener('click', () => {
          item.classList.toggle('active');
          faqItems.forEach(otherItem => {
            if (otherItem !== item && otherItem.classList.contains('active')) {
              otherItem.classList.remove('active');
            }
          });
        });
      }
    });
  }

  // Set active nav link based on current page
  const currentPath = window.location.pathname;
  let pageFile = currentPath.split('/').pop();
  if (pageFile === '' || pageFile === '/') {
    pageFile = 'index.html';
  }

  const allLinks = document.querySelectorAll('.desktop-nav a');
  allLinks.forEach(link => {
    const href = link.getAttribute('href').split('#')[0];
    if (href === pageFile || (pageFile === 'index.html' && (href === './' || href === ''))) {
      link.classList.add('active');
    }
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
  const fadeInElements = document.querySelectorAll('.fade-in');
  if (fadeInElements.length > 0) {
    fadeInElements.forEach(element => {
      // Don't pause the main hero image to ensure it animates immediately
      if (element.id === 'hero-image-main') {
        return;
      }
      element.style.animationPlayState = 'paused';
      observer.observe(element);
    });
  }
});
