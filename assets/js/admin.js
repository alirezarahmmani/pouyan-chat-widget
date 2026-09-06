(function () {
	'use strict';
	var color = document.getElementById('acw-color');
	var output = document.querySelector('output[for="acw-color"]');
	if (!color || !output) { return; }
	color.addEventListener('input', function () {
		output.textContent = color.value.toUpperCase();
	});
}());
