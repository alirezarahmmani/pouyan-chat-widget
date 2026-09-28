(function () {
	'use strict';
	var color = document.getElementById('acw-color');
	var output = document.querySelector('output[for="acw-color"]');
	if (color && output) {
		color.addEventListener('input', function () {
			output.textContent = color.value.toUpperCase();
		});
	}

	/* Live preview: mirrors unsaved form values onto the rendered widget markup. */
	var preview = document.getElementById('ai-chat-widget-preview');
	var form = document.querySelector('.acw-layout__form');
	if (!preview || !form) { return; }

	var panel = preview.querySelector('.ai-chat-widget__panel');
	var title = preview.querySelector('.ai-chat-widget__identity h2');
	var welcome = preview.querySelector('.ai-chat-widget__messages .ai-chat-widget__message');
	var samples = {
		ltr: { user: 'Do you ship internationally?', reply: 'Yes! We ship to **40+ countries**. Delivery usually takes 5–7 business days.' },
		rtl: { user: 'ارسال به شهرستان هم دارید؟', reply: 'بله! به **همه شهرها** ارسال داریم و معمولاً ۲ تا ۴ روز کاری طول می‌کشد.' }
	};

	function field(name) {
		return form.querySelector('[name="ai_chat_widget_settings[' + name + ']"]' + (name === 'position' || name === 'direction' ? ':checked' : ''));
	}

	function renderSample(text) {
		return text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
	}

	function update() {
		var direction = field('direction') ? field('direction').value : 'ltr';
		var position = field('position') ? field('position').value : 'right';
		preview.style.setProperty('--ai-chat-accent', field('primary_color').value);
		preview.classList.toggle('ai-chat-widget--left', position === 'left');
		preview.classList.toggle('ai-chat-widget--right', position !== 'left');
		panel.setAttribute('dir', direction);
		title.textContent = field('title').value;
		welcome.textContent = field('welcome').value;
		Array.prototype.forEach.call(preview.querySelectorAll('[data-acw-sample]'), function (bubble) {
			bubble.innerHTML = renderSample(samples[direction][bubble.getAttribute('data-acw-sample')]);
		});
	}

	form.addEventListener('input', update);
	form.addEventListener('change', update);
	update();
}());
