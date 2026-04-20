document.addEventListener('DOMContentLoaded', function () {

    const ratingBlocks = document.querySelectorAll('.star-rating');

    ratingBlocks.forEach(block => {

        const stars = block.querySelectorAll('.star');
        const input = block.querySelector('input[name="rating"]');

        let rating = parseFloat(input.value || 0);

        function highlight(value) {
            stars.forEach(star => {
                const starValue = parseInt(star.dataset.value);

                star.classList.remove('full', 'half');

                if (value >= starValue) {
                    star.classList.add('full');
                } else if (value >= starValue - 0.5) {
                    star.classList.add('half');
                }
            });
        }

        stars.forEach(star => {

            star.addEventListener('mousemove', (e) => {
                const rect = star.getBoundingClientRect();
                const isHalf = (e.clientX - rect.left) < rect.width / 2;

                const value = parseInt(star.dataset.value);
                const newRating = isHalf ? value - 0.5 : value;

                highlight(newRating);
            });

            star.addEventListener('click', (e) => {
                const rect = star.getBoundingClientRect();
                const isHalf = (e.clientX - rect.left) < rect.width / 2;

                const value = parseInt(star.dataset.value);
                rating = isHalf ? value - 0.5 : value;

                input.value = rating;
                highlight(rating);
            });

            star.addEventListener('mouseleave', () => {
                highlight(rating);
            });
        });

        highlight(rating);
    });

});
