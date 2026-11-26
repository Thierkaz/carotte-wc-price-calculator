(function () {
	"use strict";

	const kg = "(?:kg|kilos?|kilogrammes?|kilograms?)";
	const grams = "(?:g|gr|grammes?|grams?)";

	function parse(value, weight) {
		const normalized = String(value).trim().replace(/[\s\u00a0\u202f]+/gu, " ");
		const suffix = weight
			? "(" + kg + "|" + grams + ")?"
			: "(?:(?:€|eur(?:os?)?)?\\s*(?:(?:/|par)\\s*" + kg + ")?)";
		const match = normalized.match(
			new RegExp("^\\s*([0-9]+(?:[.,][0-9]+)?)\\s*" + suffix + "\\s*$", "iu"),
		);
		if (!match) return null;
		let amount = Number(match[1].replace(",", "."));
		if (!Number.isFinite(amount) || amount < 0 || (weight && amount <= 0)) return null;
		if (weight && match[2] && new RegExp("^" + grams + "$", "iu").test(match[2])) {
			amount /= 1000;
		}
		return amount;
	}

	function calculate(price, weight, decimals = 2) {
		price = parse(price, false);
		weight = parse(weight, true);
		if (price === null || weight === null) return null;
		decimals = Math.max(0, Math.min(8, Number(decimals)));
		const total = price * weight;
		const factor = 10 ** decimals;
		if (!Number.isFinite(total) || !Number.isFinite(total * factor)) return null;
		return (
			Math.floor(total * factor + 0.5 + Number.EPSILON * Math.max(1, total * factor)) /
			factor
		).toFixed(decimals);
	}

	if (typeof module !== "undefined" && module.exports) {
		module.exports = { calculate };
	}
	if (typeof document === "undefined") return;

	function init() {
		const config = window.carottePriceCalculator;
		const price = document.querySelector('input[name="_carotte_price_per_unit"]');
		const weight = document.querySelector('input[name="_carotte_unit"]');
		const regular = document.querySelector('input[name="_regular_price"]');
		const type = document.querySelector("#product-type");
		if (!config || !price || !weight || !regular || !type) return;

		const notice = document.createElement("p");
		notice.className = "form-field carotte-price-calculator-status";
		notice.setAttribute("role", "status");
		notice.setAttribute("aria-live", "polite");
		notice.hidden = true;
		regular.closest(".form-field").after(notice);

		function update() {
			if (type.value !== "simple") {
				notice.hidden = true;
				return;
			}
			const total = calculate(price.value, weight.value, config.decimals);
			notice.hidden = false;
			if (total === null) {
				notice.textContent = config.invalid;
				return;
			}
			notice.textContent = config.updated;
			const formatted = total.replace(".", config.separator);
			if (regular.value !== formatted) {
				regular.value = formatted;
				// Use jQuery when available so WooCommerce's own handlers also run.
				if (window.jQuery) {
					window.jQuery(regular).trigger("change");
				} else {
					regular.dispatchEvent(new Event("change", { bubbles: true }));
				}
			}
		}

		for (const field of [price, weight]) {
			field.addEventListener("input", update);
			field.addEventListener("change", update);
		}
		let previousType = type.value;
		function typeChanged() {
			if (previousType === type.value) return;
			previousType = type.value;
			update();
		}
		// WooCommerce also triggers this event through jQuery.
		if (window.jQuery) window.jQuery(type).on("change", typeChanged);
		else type.addEventListener("change", typeChanged);
	}

	if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init, { once: true });
	else init();
})();
