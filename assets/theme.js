function getCookie(name) {
  return document.cookie.split("; ").reduce((r, v) => {
    const p = v.split("=");
    return p[0] === name ? decodeURIComponent(p[1]) : r;
  }, "");
}
function setCookie(name, value, days) {
  const d = new Date();
  d.setTime(d.getTime() + days * 864e5);
  document.cookie =
    name +
    "=" +
    encodeURIComponent(value) +
    "; expires=" +
    d.toUTCString() +
    "; path=/; SameSite=Lax";
}
function setIcon(theme) {
  document.querySelectorAll('[onclick="toggleTheme()"]').forEach(function (b) {
    b.textContent = theme === "dark" ? "☀️" : "🌙";
  });
}
document.addEventListener("DOMContentLoaded", function () {
  var t = getCookie("theme") || "light";
  document.documentElement.setAttribute("data-bs-theme", t);
  setIcon(t);
});
window.toggleTheme = function () {
  var next =
    document.documentElement.getAttribute("data-bs-theme") === "dark"
      ? "light"
      : "dark";
  document.documentElement.setAttribute("data-bs-theme", next);
  setCookie("theme", next, 365);
  setIcon(next);
};
