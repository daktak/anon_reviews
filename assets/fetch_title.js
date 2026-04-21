const linkInput = document.getElementById('link');
const titleInput = document.getElementById('title');
const hint = document.getElementById('linkHint');

/*
|--------------------------------------------------------------------------
| FETCH TITLE ON BLUR
|--------------------------------------------------------------------------
*/
linkInput.addEventListener('blur', async () => {

    const url = linkInput.value.trim();

    if (!url || titleInput.value.trim() !== '') return;

    hint.textContent = 'Fetching title...';

    try {
        const res = await fetch('fetch_title.php?url=' + encodeURIComponent(url));
        const data = await res.json();

        if (data.title) {
            titleInput.value = data.title;
            hint.textContent = 'Title auto-filled';
        } else {
            hint.textContent = 'No title found';
        }

    } catch (e) {
        hint.textContent = 'Fetch failed';
    }
});
