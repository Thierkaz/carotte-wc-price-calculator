const { test } = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const { JSDOM } = require("jsdom");
const { calculate } = require("../assets/admin.js");
const cases = require("./cases.json");
const code = fs.readFileSync(path.join(__dirname, "../assets/admin.js"), "utf8");

for (const [price, weight, decimals, expected] of cases) {
	test(`calcul ${JSON.stringify([price, weight, decimals])}`, () => {
		assert.equal(calculate(price, weight, decimals), expected);
	});
}

function setup(productType = "simple", jquery = false, separator = ",") {
	const dom = new JSDOM(`
		<select id="product-type">
			<option>simple</option><option>variable</option><option>grouped</option><option>external</option>
		</select>
		<input name="_carotte_price_per_unit" value="12,50 €/kg">
		<input name="_carotte_unit" value="3,6 Kg">
		<p class="form-field"><input name="_regular_price" value="99"></p>
		<input name="_sale_price" value="2">
	`, { runScripts: "outside-only" });
	const { window } = dom;
	const type = window.document.querySelector("#product-type");
	type.value = productType;
	const callbacks = new Map();
	let changes = 0;
	if (jquery) {
		window.jQuery = (field) => ({
			on: (event, callback) => callbacks.set(field, callback),
			trigger: () => { changes++; },
		});
	}
	window.carottePriceCalculator = { decimals: "2", separator, invalid: "Saisie invalide", updated: "Prix calculé" };
	const field = (name) => window.document.querySelector(`[name="${name}"]`);
	const regular = field("_regular_price");
	regular.addEventListener("change", () => changes++);
	window.eval(code);
	window.document.dispatchEvent(new window.Event("DOMContentLoaded"));
	return {
		window, field, regular, type,
		notice: window.document.querySelector(".carotte-price-calculator-status"),
		changes: () => changes,
		edit(name, value, event = "input") {
			field(name).value = value;
			field(name).dispatchEvent(new window.Event(event, { bubbles: true }));
		},
		switchType(value) {
			type.value = value;
			if (jquery) callbacks.get(type)();
			else type.dispatchEvent(new window.Event("change"));
		},
	};
}

test("aucun écrasement à l'ouverture ; mise à jour au fil de la saisie", () => {
	const ui = setup();
	try {
		assert.equal(ui.regular.value, "99");
		assert.equal(ui.notice.hidden, true);
		ui.edit("_carotte_unit", "250gr");
		assert.equal(ui.regular.value, "3,13");
		assert.equal(ui.changes(), 1);
		assert.equal(ui.field("_sale_price").value, "2");
		ui.edit("_carotte_price_per_unit", "20 €/kg", "change");
		assert.equal(ui.regular.value, "5,00");
		assert.equal(ui.notice.textContent, "Prix calculé");
	} finally { ui.window.close(); }
});

test("une saisie invalide ne vide ni ne remplace le prix", () => {
	const ui = setup();
	try {
		ui.edit("_carotte_unit", "panier");
		assert.equal(ui.regular.value, "99");
		assert.equal(ui.notice.textContent, "Saisie invalide");
		assert.equal(ui.changes(), 0);
		ui.edit("_carotte_unit", "");
		assert.equal(ui.regular.value, "99");
	} finally { ui.window.close(); }
});

for (const type of ["variable", "grouped", "external"]) {
	test(`type ${type} exclu ; retour vers simple pris en compte`, () => {
		const ui = setup(type);
		try {
			ui.edit("_carotte_unit", "500 g");
			assert.equal(ui.regular.value, "99");
			assert.equal(ui.notice.hidden, true);
			ui.switchType("simple");
			assert.equal(ui.regular.value, "6,25");
			ui.switchType(type);
			ui.edit("_carotte_price_per_unit", "100");
			assert.equal(ui.regular.value, "6,25");
			assert.equal(ui.notice.hidden, true);
		} finally { ui.window.close(); }
	});
}

test("événements WooCommerce jQuery et séparateur point", () => {
	const ui = setup("variable", true, ".");
	try {
		ui.switchType("simple");
		assert.equal(ui.regular.value, "45.00");
		assert.equal(ui.changes(), 1);
		ui.edit("_carotte_unit", "3,6 kg");
		assert.equal(ui.changes(), 1);
	} finally { ui.window.close(); }
});

test("l'initialisation WooCommerce ne recalcule pas un simple à l'ouverture", () => {
	const ui = setup("simple", true);
	try {
		ui.switchType("simple");
		assert.equal(ui.regular.value, "99");
		assert.equal(ui.changes(), 0);
		assert.equal(ui.notice.hidden, true);
	} finally { ui.window.close(); }
});

test("absence des champs du thème : aucune erreur", () => {
	const dom = new JSDOM("", { runScripts: "outside-only" });
	try {
		dom.window.eval(code);
		dom.window.document.dispatchEvent(new dom.window.Event("DOMContentLoaded"));
		assert.equal(dom.window.document.querySelector(".carotte-price-calculator-status"), null);
	} finally { dom.window.close(); }
});
