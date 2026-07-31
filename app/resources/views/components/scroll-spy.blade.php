@props(['sidebar'])

@push('scripts')
<script>
(function () {
    const links = Array.from(document.querySelectorAll('#{{ $sidebar }} a[href^="#"]'));
    const targets = links
        .map((a) => document.getElementById(a.getAttribute('href').slice(1)))
        .filter(Boolean);

    if (!targets.length) return;

    const setActive = (id) => {
        links.forEach((a) => a.classList.toggle('active', a.getAttribute('href') === `#${id}`));
    };

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

            if (visible.length) setActive(visible[0].target.id);
        },
        { rootMargin: '0px 0px -70% 0px', threshold: 0 }
    );

    targets.forEach((el) => observer.observe(el));
})();
</script>
@endpush
