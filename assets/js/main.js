document.addEventListener("DOMContentLoaded", function () {
  var buttons = document.querySelectorAll(".saber-mais");

  buttons.forEach(function (button) {
    button.addEventListener("click", function () {
      var panel = document.getElementById(button.getAttribute("data-target"));
      if (!panel) return;

      var isOpen = panel.classList.toggle("is-open");
      button.setAttribute("aria-expanded", isOpen ? "true" : "false");

      var label = button.querySelector(".label");
      if (label) {
        label.textContent = isOpen ? "Saber menos" : "Saber mais";
      }
    });
  });
});
