document.addEventListener('DOMContentLoaded', () => {
  const alerts = document.querySelectorAll('[data-close]');
  alerts.forEach((button) => {
    button.addEventListener('click', () => button.closest('.flash')?.remove());
  });

  const amountInputs = document.querySelectorAll('input[data-money]');
  amountInputs.forEach((input) => {
    input.addEventListener('input', () => {
      input.value = input.value.replace(',', '.').replace(/[^0-9.]/g, '');
    });
  });
});
