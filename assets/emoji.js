const emojiList = [
    "😀","😁","😂","🤣","😃","😄","😅","😆",
    "😉","😊","😍","😎","🤔","😢","😭","😡",
    "🔥","❤️","💀","✨","🎉",
];

const grid = document.getElementById('emojiGrid');
const display = document.getElementById('emojiDisplay');
const input = document.getElementById('emoji');

function setEmoji(e) {
    input.value = e;
    display.textContent = e;
}

function clearEmoji() {
    input.value = '';
    display.textContent = '—';
}

emojiList.forEach(e => {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'btn btn-light';
    b.textContent = e;
    b.onclick = () => setEmoji(e);
    grid.appendChild(b);
});
