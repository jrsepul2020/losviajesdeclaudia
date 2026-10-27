/* Cierra los desplegables (<details>) al pulsar fuera o Escape, y abre solo uno a la vez. */
(() => {
	const all = () => document.querySelectorAll('.site-header details[open]');
	document.addEventListener('click', (e) => {
		all().forEach((d) => { if (!d.contains(e.target)) d.removeAttribute('open'); });
	});
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') all().forEach((d) => d.removeAttribute('open'));
	});
	document.querySelectorAll('.site-header details').forEach((d) => {
		d.addEventListener('toggle', () => {
			if (d.open) {
				all().forEach((o) => { if (o !== d && !o.contains(d)) o.removeAttribute('open'); });
				const input = d.querySelector('input[type="search"]');
				if (input && d.classList.contains('search-toggle')) input.focus();
			}
		});
	});
})();

/* Carruseles: flechas (ordenador) y puntos (móvil) sincronizados con el scroll nativo. */
(() => {
	const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
	document.querySelectorAll('.rail').forEach((rail) => {
		const track = rail.querySelector('.rail__track');
		const cards = [...track.children];
		const prev = rail.querySelector('.rail__btn[data-dir="-1"]');
		const next = rail.querySelector('.rail__btn[data-dir="1"]');
		const dotsBox = rail.querySelector('.rail__dots');
		if (!cards.length) return;

		const go = (el) => track.scrollTo({ left: el.offsetLeft - parseFloat(getComputedStyle(track).scrollPaddingLeft || 0), behavior: reduce ? 'auto' : 'smooth' });
		const dots = cards.map((card, i) => {
			const b = document.createElement('button');
			b.type = 'button';
			b.tabIndex = -1;
			b.addEventListener('click', () => go(card));
			dotsBox.appendChild(b);
			return b;
		});

		const update = () => {
			const max = track.scrollWidth - track.clientWidth - 2;
			if (prev) prev.disabled = track.scrollLeft <= 2;
			if (next) next.disabled = track.scrollLeft >= max;
			const center = track.scrollLeft + track.clientWidth / 2;
			let active = 0, best = Infinity;
			cards.forEach((c, i) => { const d = Math.abs(c.offsetLeft + c.offsetWidth / 2 - center); if (d < best) { best = d; active = i; } });
			if (track.scrollLeft >= max) active = cards.length - 1;
			dots.forEach((d, i) => d.setAttribute('aria-current', i === active ? 'true' : 'false'));
		};

		[prev, next].forEach((btn) => btn && btn.addEventListener('click', () => {
			track.scrollBy({ left: Number(btn.dataset.dir) * track.clientWidth * 0.85, behavior: reduce ? 'auto' : 'smooth' });
		}));
		let raf = 0;
		track.addEventListener('scroll', () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(update); }, { passive: true });
		addEventListener('resize', update, { passive: true });
		update();
	});
})();
