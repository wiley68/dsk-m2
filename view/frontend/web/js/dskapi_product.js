let old_vnoski;

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

function dskapi_pogasitelni_vnoski_input_focus(_old_vnoski) {
    old_vnoski = _old_vnoski;
}

function dskapi_pogasitelni_vnoski_input_change() {
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
                    const dskapi_vnoska_input =
                        document.getElementById("dskapi_vnoska");
                    const dskapi_gpr = document.getElementById("dskapi_gpr");
                    const dskapi_obshtozaplashtane_input =
                        document.getElementById("dskapi_obshtozaplashtane");
                    dskapi_vnoska_input.value = dsk_vnoska.toFixed(2);
                    dskapi_gpr.value = dsk_gpr.toFixed(2);
                    dskapi_obshtozaplashtane_input.value = (
                        dsk_vnoska * dskapi_vnoski
                    ).toFixed(2);
                    old_vnoski = dskapi_vnoski;
                } else {
                    alert(
                        "Избраният брой погасителни вноски е под минималния."
                    );
                    var dskapi_vnoski_input = document.getElementById(
                        "dskapi_pogasitelni_vnoski_input"
                    );
                    dskapi_vnoski_input.value = old_vnoski;
                }
            } else {
                alert("Избраният брой погасителни вноски е над максималния.");
                var dskapi_vnoski_input = document.getElementById(
                    "dskapi_pogasitelni_vnoski_input"
                );
                dskapi_vnoski_input.value = old_vnoski;
            }
        }
    };
    xmlhttpro.send();
}

function calculateDskapiPriceWithOptions(
    dskapi_eur,
    dskapi_currency_code,
    dskapi_price1
) {
    let dskapi_quantity = 1;
    const qtyInputs = document.getElementsByName("qty");
    if (qtyInputs !== null && qtyInputs[0]) {
        dskapi_quantity = parseFloat(qtyInputs[0].value);
        if (dskapi_quantity === 0) {
            dskapi_quantity = 1;
        }
    }

    let pr = dskapi_price1;
    const offers = document.querySelectorAll('[itemprop="offers"]');
    if (typeof offers[0] !== "undefined") {
        const span1 = offers[0].querySelectorAll(
            '[data-price-type="finalPrice"]'
        );
        if (typeof span1[0] !== "undefined") {
            const span2 = span1[0].innerText;
            if (typeof span2 !== "undefined") {
                pr = span2;
            }
        }
    }

    let parsed = pr.replace(/[^\d.,-]/g, "");
    if (parsed[parsed.length - 1] === ".") {
        parsed = parsed.slice(0, -1);
    }
    if (parsed.indexOf(".") !== -1) {
        parsed = parsed.replace(/[^\d.-]/g, "");
    } else {
        parsed = parsed.replace(/,/g, ".");
    }
    parsed = parseFloat(parsed);

    let dskapi_priceall = parseFloat(parsed) * dskapi_quantity;

    switch (dskapi_eur) {
        case 0:
            break;
        case 1:
            if (dskapi_currency_code === "EUR") {
                dskapi_priceall = dskapi_priceall * 1.95583;
            }
            break;
        case 2:
            if (dskapi_currency_code === "BGN") {
                dskapi_priceall = dskapi_priceall / 1.95583;
            }
            break;
    }

    return dskapi_priceall;
}

function updateDskapiPriceDisplay(dskapi_priceall) {
    const dskapi_price_txt = document.getElementById("dskapi_price_txt");
    if (dskapi_price_txt) {
        dskapi_price_txt.value = dskapi_priceall.toFixed(2);
    }
}

/**
 * Updates button values dynamically after price change
 * Makes AJAX request to calculate new installments and displays them in the button
 * Called when product options or quantity change and popup is not open
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

    const dskapi_price = document.getElementById("dskapi_price");
    const dskapi_priceall = calculateDskapiPriceWithOptions(
        dskapi_eur,
        dskapi_currency_code,
        dskapi_price.value
    );

    if (dskapi_priceall > parseFloat(dskapi_maxstojnost)) {
        return;
    }

    const dskapi_vnoski_txt = document.getElementById("dskapi_vnoski_txt");
    if (dskapi_vnoski_txt) {
        const dskapi_pogasitelni_vnoski_input = document.getElementById(
            "dskapi_pogasitelni_vnoski_input"
        );
        let defaultVnoski = 12;
        if (dskapi_vnoski_txt.textContent) {
            defaultVnoski = parseInt(dskapi_vnoski_txt.textContent);
        } else if (dskapi_pogasitelni_vnoski_input) {
            defaultVnoski =
                parseInt(dskapi_pogasitelni_vnoski_input.value) || 12;
        }

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

function updateDskapiCalculations() {
    const dskapi_price = document.getElementById("dskapi_price");
    const dskapi_price_elem_value = dskapi_price ? dskapi_price.value : 0;
    const dskapi_eur = parseInt(document.getElementById("dskapi_eur").value);
    const dskapi_currency_code = document.getElementById(
        "dskapi_currency_code"
    ).value;
    const dskapi_priceall = calculateDskapiPriceWithOptions(
        dskapi_eur,
        dskapi_currency_code,
        dskapi_price_elem_value
    );
    updateDskapiPriceDisplay(dskapi_priceall);

    const dskapiProductPopupContainer = document.getElementById(
        "dskapi-product-popup-container"
    );
    if (
        dskapiProductPopupContainer &&
        dskapiProductPopupContainer.style.display === "block"
    ) {
        dskapi_pogasitelni_vnoski_input_change();
    } else {
        updateDskapiButtonValues();
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const btn_dskapi = document.getElementById("btn_dskapi");
    if (btn_dskapi !== null) {
        const dskapi_button_status = parseInt(
            document.getElementById("dskapi_button_status").value
        );
        const dskapiProductPopupContainer = document.getElementById(
            "dskapi-product-popup-container"
        );
        const dskapi_back_credit =
            document.getElementById("dskapi_back_credit");
        const dskapi_buy_credit = document.getElementById("dskapi_buy_credit");
        const dskapi_buy_buttons_submit = document.querySelectorAll(
            "button#product-addtocart-button"
        );

        const dskapi_price = document.getElementById("dskapi_price");
        const dskapi_maxstojnost =
            document.getElementById("dskapi_maxstojnost");
        const dskapi_price1 = dskapi_price ? dskapi_price.value : 0;

        btn_dskapi.addEventListener("click", (event) => {
            const dskapi_eur = parseInt(
                document.getElementById("dskapi_eur").value
            );
            const dskapi_currency_code = document.getElementById(
                "dskapi_currency_code"
            ).value;

            if (dskapi_button_status == 1) {
                if (dskapi_buy_buttons_submit.length) {
                    sessionStorage.setItem(
                        "autoSelectPaymentMethod",
                        "dskapipaymentmethod"
                    );
                    dskapi_buy_buttons_submit.item(0).click();
                    setTimeout(() => {
                        window.location.href =
                            "/checkout/?payment_method=dskapipaymentmethod";
                    }, 1500);
                }
            } else {
                const dskapi_priceall = calculateDskapiPriceWithOptions(
                    dskapi_eur,
                    dskapi_currency_code,
                    dskapi_price1
                );

                updateDskapiPriceDisplay(dskapi_priceall);

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
            if (dskapi_buy_buttons_submit.length) {
                sessionStorage.setItem(
                    "autoSelectPaymentMethod",
                    "dskapipaymentmethod"
                );
                dskapi_buy_buttons_submit.item(0).click();
                setTimeout(() => {
                    window.location.href =
                        "/checkout/?payment_method=dskapipaymentmethod";
                }, 1500);
            }
        });

        const qtyInputs = document.querySelectorAll('input[name="qty"]');
        if (qtyInputs.length > 0) {
            qtyInputs.forEach((qtyInput) => {
                qtyInput.addEventListener("change", function () {
                    updateDskapiCalculations();
                });
            });
        }

        const dskapiSpanOffers = document.querySelectorAll(
            '[itemprop="offers"]'
        );
        if (typeof dskapiSpanOffers[0] !== "undefined") {
            const dskapiSpanFinalPrice = dskapiSpanOffers[0].querySelectorAll(
                '[data-price-type="finalPrice"]'
            );
            if (typeof dskapiSpanFinalPrice[0] !== "undefined") {
                const dskapiTargetNode = dskapiSpanFinalPrice[0];
                if (
                    dskapiTargetNode !== null &&
                    dskapiTargetNode instanceof Node
                ) {
                    const observer = new MutationObserver(mutationCallback);
                    const config = {
                        childList: true,
                        subtree: true,
                    };
                    function mutationCallback(mutationsList, observer) {
                        updateDskapiCalculations();
                    }
                    observer.observe(dskapiTargetNode, config);
                }
            }
        }
    }
});
