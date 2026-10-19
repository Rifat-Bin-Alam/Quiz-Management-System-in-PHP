/**
 * Quiz Management System — University Examination Portal Client Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
  // Mobile Navigation Menu Toggle
  const navbarToggle = document.getElementById('navbarToggle');
  const navbarLinks = document.getElementById('navbarLinks');

  if (navbarToggle && navbarLinks) {
    navbarToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isExpanded = navbarToggle.getAttribute('aria-expanded') === 'true';
      navbarToggle.setAttribute('aria-expanded', !isExpanded);
      navbarLinks.classList.toggle('is-open', !isExpanded);
      navbarToggle.classList.toggle('is-active', !isExpanded);
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!navbarToggle.contains(e.target) && !navbarLinks.contains(e.target)) {
        navbarToggle.setAttribute('aria-expanded', 'false');
        navbarLinks.classList.remove('is-open');
        navbarToggle.classList.remove('is-active');
      }
    });

    // Close menu when pressing Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navbarLinks.classList.contains('is-open')) {
        navbarToggle.setAttribute('aria-expanded', 'false');
        navbarLinks.classList.remove('is-open');
        navbarToggle.classList.remove('is-active');
        navbarToggle.focus();
      }
    });

    // Close menu on navigation link click (on mobile)
    navbarLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          navbarToggle.setAttribute('aria-expanded', 'false');
          navbarLinks.classList.remove('is-open');
          navbarToggle.classList.remove('is-active');
        }
      });
    });
  }

  // Password Visibility Toggle
  document.querySelectorAll('.password-toggle-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = btn.getAttribute('data-target') || 'password';
      const input = document.getElementById(targetId);
      if (!input) return;

      const isPassword = input.getAttribute('type') === 'password';
      input.setAttribute('type', isPassword ? 'text' : 'password');

      btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      const icon = btn.querySelector('svg');
      if (icon) {
        if (isPassword) {
          // Eye slash icon
          icon.innerHTML = '<path d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" fill="currentColor"/><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.742L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z" fill="currentColor"/>';
        } else {
          // Normal eye icon
          icon.innerHTML = '<path d="M10 12a2 2 0 100-4 2 2 0 000 4z" fill="currentColor"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" fill="currentColor"/>';
        }
      }
    });
  });

  // Automatically dismiss alerts after 8 seconds
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s ease-out';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 400);
    }, 8000);
  });

  // Demo Login Credentials Autofill Handler
  document.querySelectorAll('.demo-autofill-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const email = btn.getAttribute('data-email') || '';
      const password = btn.getAttribute('data-password') || '';
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');

      if (emailInput) {
        emailInput.value = email;
        emailInput.dispatchEvent(new Event('input', { bubbles: true }));
        emailInput.focus();
      }
      if (passwordInput) {
        passwordInput.value = password;
        passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
  });

  // =========================================================================
  // Examination Room Controller (quiz_attempt.php)
  // =========================================================================
  const quizForm = document.getElementById('quiz-attempt-form');
  if (quizForm) {
    const totalQuestions = parseInt(quizForm.getAttribute('data-total') || '0', 10);
    const modal = document.getElementById('examSubmitModal');
    const openModalBtn = document.getElementById('examOpenModalBtn');
    const cancelModalBtn = document.getElementById('modalCancelBtn');
    const confirmSubmitBtn = document.getElementById('modalConfirmSubmitBtn');
    const answeredCountEl = document.getElementById('answered-count');
    const remainingCountEl = document.getElementById('remaining-count');
    const progressPercentEl = document.getElementById('progress-percent');
    const progressBarEl = document.getElementById('exam-progress-bar');
    const modalAnsweredEl = document.getElementById('modal-answered-count');
    const modalUnansweredEl = document.getElementById('modal-unanswered-count');
    const modalWarningEl = document.getElementById('modal-warning-text');
    const modalReadyEl = document.getElementById('modal-ready-text');
    let submissionConfirmed = false;

    function updateExamProgress() {
      // Find all checked radios in the form
      const checkedRadios = quizForm.querySelectorAll('input[type="radio"]:checked');
      const answeredQids = new Set();
      checkedRadios.forEach(radio => {
        const qid = radio.getAttribute('data-qid');
        if (qid) answeredQids.add(qid);
      });

      const answeredCount = answeredQids.size;
      const remainingCount = Math.max(0, totalQuestions - answeredCount);
      const percent = totalQuestions > 0 ? Math.round((answeredCount / totalQuestions) * 100) : 0;

      if (answeredCountEl) answeredCountEl.textContent = answeredCount;
      if (remainingCountEl) remainingCountEl.textContent = remainingCount;
      if (progressPercentEl) progressPercentEl.textContent = `${percent}% Complete`;
      if (progressBarEl) {
        progressBarEl.style.width = `${percent}%`;
        const track = progressBarEl.closest('.exam-progress-track');
        if (track) track.setAttribute('aria-valuenow', percent);
      }

      // Update question cards & question palette jump buttons
      quizForm.querySelectorAll('.question-card').forEach(card => {
        const qid = card.getAttribute('data-qid');
        const isAnswered = answeredQids.has(qid);
        const statusPill = document.getElementById(`qstatus-${qid}`);
        const palettePill = document.getElementById(`nav-pill-${qid}`);

        if (isAnswered) {
          card.classList.add('is-answered');
          if (statusPill) {
            statusPill.classList.remove('unanswered');
            statusPill.classList.add('answered');
            statusPill.textContent = 'Answered';
          }
          if (palettePill) {
            palettePill.classList.remove('unanswered');
            palettePill.classList.add('answered');
          }
        } else {
          card.classList.remove('is-answered');
          if (statusPill) {
            statusPill.classList.remove('answered');
            statusPill.classList.add('unanswered');
            statusPill.textContent = 'Unanswered';
          }
          if (palettePill) {
            palettePill.classList.remove('answered');
            palettePill.classList.add('unanswered');
          }
        }
      });

      // Update modal stats
      if (modalAnsweredEl) modalAnsweredEl.textContent = answeredCount;
      if (modalUnansweredEl) modalUnansweredEl.textContent = remainingCount;
      if (modalWarningEl && modalReadyEl) {
        if (remainingCount > 0) {
          modalWarningEl.style.display = 'block';
          modalReadyEl.style.display = 'none';
        } else {
          modalWarningEl.style.display = 'none';
          modalReadyEl.style.display = 'block';
        }
      }
    }

    // Radio change listener: selection style and state update
    quizForm.querySelectorAll('input[type="radio"]').forEach(radio => {
      // Initialize pre-checked state
      if (radio.checked) {
        const parent = radio.closest('.option-label');
        if (parent) parent.classList.add('selected');
      }

      radio.addEventListener('change', () => {
        const groupName = radio.getAttribute('name');
        quizForm.querySelectorAll(`input[type="radio"][name="${groupName}"]`).forEach(r => {
          const parent = r.closest('.option-label');
          if (parent) parent.classList.remove('selected');
        });
        const activeParent = radio.closest('.option-label');
        if (activeParent) activeParent.classList.add('selected');

        updateExamProgress();
      });
    });

    // Palette jump smooth scroll
    document.querySelectorAll('.palette-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = btn.getAttribute('href');
        const targetEl = document.querySelector(targetId);
        if (targetEl) {
          targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    // Modal open & close
    function openModal() {
      if (modal) {
        updateExamProgress();
        modal.classList.add('is-active');
        document.body.style.overflow = 'hidden';
      }
    }

    function closeModal() {
      if (modal) {
        modal.classList.remove('is-active');
        document.body.style.overflow = '';
      }
    }

    if (openModalBtn) {
      openModalBtn.addEventListener('click', (e) => {
        e.preventDefault();
        openModal();
      });
    }

    if (cancelModalBtn) {
      cancelModalBtn.addEventListener('click', (e) => {
        e.preventDefault();
        closeModal();
      });
    }

    // Close on backdrop click
    if (modal) {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          closeModal();
        }
      });
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal && modal.classList.contains('is-active')) {
        closeModal();
      }
    });

    // Intercept native form submit (e.g. Enter key) unless explicitly confirmed via modal
    quizForm.addEventListener('submit', (e) => {
      if (!submissionConfirmed) {
        e.preventDefault();
        openModal();
      }
    });

    // Confirm submission button inside modal
    if (confirmSubmitBtn) {
      confirmSubmitBtn.addEventListener('click', () => {
        submissionConfirmed = true;
        confirmSubmitBtn.disabled = true;
        confirmSubmitBtn.textContent = 'Submitting Examination...';
        quizForm.submit();
      });
    }

    // Initial check
    updateExamProgress();
  }
});
