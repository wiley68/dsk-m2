/**
 * Global variable to store the previous installment count value
 * Used to restore the value if user selects an invalid installment count
 * @type {number}
 */
let old_vnoski_dskapi;

/**
 * Creates a CORS-enabled XMLHttpRequest for cross-origin requests
 * @param {string} method - HTTP method (GET, POST, etc.)
 * @param {string} url - Target URL for the request
 * @returns {XMLHttpRequest|XDomainRequest|null} The XHR object or null if not supported
 */
function createCORSRequest(method, url) {
    var xhr = new XMLHttpRequest();
    if ("withCredentials" in xhr) {
        xhr.open(method, url, true);
    } else if (typeof XDomainRequest != "undefined") {
        xhr = new XDomainRequest();
        xhr.open(method, url);
    } else {
        xhr = null;
    }
    return xhr;
}

/**
 * Stores the old number of installments before user changes it
 * Used to restore the value if the new selection is invalid
 * @param {string} _old_vnoski - The previous installment count value
 */
function dskapi_pogasitelni_vnoski_input_focus(_old_vnoski) {
    old_vnoski_dskapi = _old_vnoski;
}

/**
 * Calculates the cart price considering currency options.
 * @param {number} dskapi_eur - EUR code for currency conversion (0, 1, or 2)
 * @param {string} dskapi_currency_code - Current currency code (EUR or BGN)
 * @param {number} dskapi_price - Base price
 * @returns {number} Calculated price
 */
function calculateDskapiCartPrice(
    dskapi_eur,
    dskapi_currency_code,
    dskapi_price
) {
    // Parse to number
    const dskapi_price_parsed = parseFloat(dskapi_price);

    // Apply currency conversions
    let dskapi_priceall = dskapi_price_parsed;
    switch (dskapi_eur) {
        case 0:
            dskapi_priceall = dskapi_price_parsed;
            break;
        case 1:
            if (dskapi_currency_code == "EUR") {
                dskapi_priceall = dskapi_price_parsed * 1.95583;
            }
            break;
        case 2:
            if (dskapi_currency_code == "BGN") {
                dskapi_priceall = dskapi_price_parsed / 1.95583;
            }
            break;
    }

    return dskapi_priceall;
}

/**
 * Updates the price display in the popup based on calculated price.
 * @param {number} dskapi_priceall - The calculated total price
 */
function updateDskapiCartPriceDisplay(dskapi_priceall) {
    const dskapi_price_txt = document.getElementById("dskapi_price_txt");
    if (dskapi_price_txt) {
        dskapi_price_txt.value = dskapi_priceall.toFixed(2);
    }
}

/**
 * Dynamically updates the button values after price changes.
 * Sends an AJAX request to compute new installments and reflects them on the button.
 */
function updateDskapiButtonValues() {
    const dskapi_cid = document.getElementById("dskapi_cid").value;
    const DSKAPI_LIVEURL = document.getElementById("DSKAPI_LIVEURL").value;
    const dskapi_product_id =
        document.getElementById("dskapi_product_id").value;
    const dskapi_eur = parseInt(document.getElementById("dskapi_eur").value);
    const dskapi_currency_code = document.getElementById(
        "dskapi_currency_code"
    ).value;
    const dskapi_maxstojnost =
        document.getElementById("dskapi_maxstojnost").value;

    // Calculate the new price
    const dskapi_price_elem = document.getElementById("dskapi_price").value;
    const dskapi_priceall = calculateDskapiCartPrice(
        dskapi_eur,
        dskapi_currency_code,
        dskapi_price_elem
    );

    // Check if the price exceeds the maximum
    if (dskapi_priceall > parseFloat(dskapi_maxstojnost)) {
        return; // Do not update if it exceeds the maximum
    }

    // Get the default number of installments
    const dskapi_vnoski_txt = document.getElementById("dskapi_vnoski_txt");
    if (dskapi_vnoski_txt) {
        // Extract default number of installments from text
        let defaultVnoski = 12; // fallback стойност
        if (dskapi_vnoski_txt.textContent) {
            defaultVnoski = parseInt(dskapi_vnoski_txt.textContent);
        } else {
            // Try to get it from the hidden input
            const dskapi_vnoski_input = document.getElementById(
                "dskapi_pogasitelni_vnoski_input"
            );
            if (dskapi_vnoski_input) {
                defaultVnoski = parseInt(dskapi_vnoski_input.value) || 12;
            }
        }

        // AJAX request to calculate new installments
        const xmlhttpro = createCORSRequest(
            "GET",
            DSKAPI_LIVEURL +
                "/function/getproductcustom.php?cid=" +
                dskapi_cid +
                "&price=" +
                dskapi_priceall +
                "&product_id=" +
                dskapi_product_id +
                "&dskapi_vnoski=" +
                defaultVnoski
        );

        xmlhttpro.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                try {
                    const response = JSON.parse(this.response);
                    const dsk_vnoska = parseFloat(response.dsk_vnoska);
                    const options = response.dsk_options;
                    const dsk_is_visible = response.dsk_is_visible;

                    if (dsk_is_visible && options) {
                        const dskapi_vnoska_txt =
                            document.getElementById("dskapi_vnoska_txt");

                        if (dskapi_vnoski_txt) {
                            dskapi_vnoski_txt.textContent = defaultVnoski;
                        }

                        if (dskapi_vnoska_txt) {
                            dskapi_vnoska_txt.textContent =
                                dsk_vnoska.toFixed(2);
                        }
                    }
                } catch (e) {
                    console.error("Error parsing Dskapi response:", e);
                }
            }
        };

        xmlhttpro.send();
    }
}

/**
 * Handles installment count change in the popup
 * Sends AJAX request to recalculate installment amounts based on new count
 * Updates both popup fields and button values dynamically
 */
function dskapi_pogasitelni_vnoski_input_change() {
    const dskapi_eur = parseInt(document.getElementById("dskapi_eur").value);
    const dskapi_vnoski = parseFloat(
        document.getElementById("dskapi_pogasitelni_vnoski_input").value
    );
    const dskapi_price = parseFloat(
        document.getElementById("dskapi_price_txt").value
    );
    const dskapi_cid = document.getElementById("dskapi_cid").value;
    const DSKAPI_LIVEURL = document.getElementById("DSKAPI_LIVEURL").value;
    const dskapi_product_id =
        document.getElementById("dskapi_product_id").value;
    var xmlhttpro = createCORSRequest(
        "GET",
        DSKAPI_LIVEURL +
            "/function/getproductcustom.php?cid=" +
            dskapi_cid +
            "&price=" +
            dskapi_price +
            "&product_id=" +
            dskapi_product_id +
            "&dskapi_vnoski=" +
            dskapi_vnoski
    );
    xmlhttpro.onreadystatechange = function () {
        if (this.readyState == 4) {
            var options = JSON.parse(this.response).dsk_options;
            var dsk_vnoska = parseFloat(JSON.parse(this.response).dsk_vnoska);
            var dsk_gpr = parseFloat(JSON.parse(this.response).dsk_gpr);
            var dsk_is_visible = JSON.parse(this.response).dsk_is_visible;
            if (dsk_is_visible) {
                if (options) {
                    const dskapi_vnoski_txt =
                        document.getElementById("dskapi_vnoski_txt");
                    if (dskapi_vnoski_txt) {
                        dskapi_vnoski_txt.textContent = dskapi_vnoski;
                    }
                    const dskapi_vnoska_input =
                        document.getElementById("dskapi_vnoska");
                    const dskapi_gpr = document.getElementById("dskapi_gpr");
                    const dskapi_obshtozaplashtane_input =
                        document.getElementById("dskapi_obshtozaplashtane");
                    const dskapi_vnoska_txt =
                        document.getElementById("dskapi_vnoska_txt");
                    if (dskapi_vnoska_txt) {
                        dskapi_vnoska_txt.textContent = dsk_vnoska.toFixed(2);
                    }
                    dskapi_vnoska_input.value = dsk_vnoska.toFixed(2);
                    dskapi_obshtozaplashtane_input.value = (
                        dsk_vnoska * dskapi_vnoski
                    ).toFixed(2);
                    dskapi_gpr.value = dsk_gpr.toFixed(2);
                    old_vnoski_dskapi = dskapi_vnoski;
                } else {
                    alert(
                        "Избраният брой погасителни вноски е под минималния."
                    );
                    var dskapi_vnoski_input = document.getElementById(
                        "dskapi_pogasitelni_vnoski_input"
                    );
                    dskapi_vnoski_input.value = old_vnoski_dskapi;
                }
            } else {
                alert("Избраният брой погасителни вноски е над максималния.");
                var dskapi_vnoski_input = document.getElementById(
                    "dskapi_pogasitelni_vnoski_input"
                );
                dskapi_vnoski_input.value = old_vnoski_dskapi;
            }
        }
    };
    xmlhttpro.send();
}

/**
 * Initializes event handlers for buttons and the popup on the cart page,
 * and sets up automatic calculation/updating of values.
 */
document.addEventListener("DOMContentLoaded", function () {
    const btn_dskapi_cart = document.getElementById("btn_dskapi_cart");
    if (btn_dskapi_cart !== null) {
        const dskapi_button_status = parseInt(
            document.getElementById("dskapi_button_status").value
        );
        const dskapiProductPopupContainer = document.getElementById(
            "dskapi-product-popup-container"
        );
        const dskapi_back_credit =
            document.getElementById("dskapi_back_credit");
        const dskapi_buy_credit = document.getElementById("dskapi_buy_credit");

        const dskapi_price = document.getElementById("dskapi_price").value;
        const dskapi_maxstojnost =
            document.getElementById("dskapi_maxstojnost");

        btn_dskapi_cart.addEventListener("click", (event) => {
            const dskapi_eur = parseInt(
                document.getElementById("dskapi_eur").value
            );
            const dskapi_currency_code = document.getElementById(
                "dskapi_currency_code"
            ).value;
            if (dskapi_button_status == 1) {
                // Store the selected payment method in sessionStorage
                sessionStorage.setItem(
                    "autoSelectPaymentMethod",
                    "dskapipaymentmethod"
                );

                window.location.href =
                    "/checkout/?payment_method=dskapipaymentmethod";
            } else {
                // Calculate the price with options
                const dskapi_priceall = calculateDskapiCartPrice(
                    dskapi_eur,
                    dskapi_currency_code,
                    dskapi_price
                );

                // Update the price display
                updateDskapiCartPriceDisplay(dskapi_priceall);

                if (dskapi_priceall <= parseFloat(dskapi_maxstojnost.value)) {
                    dskapiProductPopupContainer.style.display = "block";
                    dskapi_pogasitelni_vnoski_input_change();
                } else {
                    alert(
                        "Максимално позволената цена за кредит " +
                            parseFloat(dskapi_maxstojnost.value).toFixed(2) +
                            " е надвишена!"
                    );
                }
            }
        });

        dskapi_back_credit.addEventListener("click", (event) => {
            dskapiProductPopupContainer.style.display = "none";
        });

        dskapi_buy_credit.addEventListener("click", (event) => {
            dskapiProductPopupContainer.style.display = "none";
            // Store the selected payment method in sessionStorage
            sessionStorage.setItem(
                "autoSelectPaymentMethod",
                "dskapipaymentmethod"
            );

            // Redirect to checkout with payment method pre-selected
            window.location.href =
                "/checkout/?payment_method=dskapipaymentmethod";
        });
    }
});
