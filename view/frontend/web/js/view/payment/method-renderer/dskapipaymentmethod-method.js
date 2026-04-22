/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
    "jquery",
    "Magento_Checkout/js/view/payment/default",
    "ko",
    "Magento_Checkout/js/model/quote",
], function ($, Component, ko, quote) {
    "use strict";

    var self;

    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: "Avalon_Dskapipayment/payment/dskapipaymentmethod",
        },

        /** Returns send check to info */
        getMailingAddress: function () {
            return window.checkoutConfig.payment.checkmo.mailingAddress;
        },

        dskapiloading: ko.observable(true),
        dskapiStatus: ko.observable(""),

        // Popup observables
        dskapiCheckoutPicture: ko.observable(""),
        dskapiCheckoutSign: ko.observable("лв."),
        dskapiCheckoutPrice: ko.observable(""),
        dskapiCheckoutVnoskiOptions: ko.observableArray([]),
        dskapiCheckoutSelectedVnoski: ko.observable(12),
        dskapiCheckoutVnoska: ko.observable(""),
        dskapiCheckoutObshtozaplashtane: ko.observable(""),
        dskapiCheckoutGpr: ko.observable(""),
        dskapiCheckoutModuleVersion: ko.observable(""),
        dskapiCheckoutProductId: ko.observable(0),
        dskapiCheckoutCid: ko.observable(""),
        dskapiCheckoutLiveUrl: ko.observable(""),
        dskapiCheckoutEur: ko.observable(0),
        dskapiCheckoutCurrencyCode: ko.observable("BGN"),
        dskapiCheckoutMaxstojnost: ko.observable(0),
        oldDskapiCheckoutVnoski: 12,

        initialize: function () {
            self = this;
            this._super();
            this.getCheckoutDskapi();

            // Initialize popup handlers
            this.onDskapiCheckoutVnoskiFocus = function (data, event) {
                self.oldDskapiCheckoutVnoski =
                    self.dskapiCheckoutSelectedVnoski();
            };

            this.onDskapiCheckoutVnoskiChange = function (data, event) {
                self.updateDskapiCheckoutInstallments();
            };

            // Auto-select this payment method if URL parameter or sessionStorage is present
            var urlParams = new URLSearchParams(window.location.search);
            var autoSelectPaymentMethod = sessionStorage.getItem(
                "autoSelectPaymentMethod"
            );

            if (
                urlParams.get("payment_method") === "dskapipaymentmethod" ||
                autoSelectPaymentMethod === "dskapipaymentmethod"
            ) {
                setTimeout(() => {
                    this.selectPaymentMethod();
                    // Clear the sessionStorage after using it
                    sessionStorage.removeItem("autoSelectPaymentMethod");
                }, 500);
            }
        },

        getDskapiStatus: function () {
            if (self.dskapiStatus() == "Yes") {
                return true;
            } else {
                return false;
            }
        },

        getCheckoutDskapi: function () {
            self.dskapiloading(true);
            $.ajax({
                url: "/dskapipayment/index/dskapigetcheckout",
                type: "post",
                dataType: "json",
                data: {},
                success: function (json) {
                    self.dskapiStatus(json["dskapi_status"]);
                    if (json["dskapi_product_id"] !== undefined) {
                        self.dskapiCheckoutProductId(json["dskapi_product_id"]);
                    }
                    // Store configuration values
                    if (json["dskapi_cid"] !== undefined) {
                        self.dskapiCheckoutCid(json["dskapi_cid"]);
                    }
                    if (json["dskapi_live_url"] !== undefined) {
                        self.dskapiCheckoutLiveUrl(json["dskapi_live_url"]);
                    }
                    if (json["dskapi_eur"] !== undefined) {
                        self.dskapiCheckoutEur(json["dskapi_eur"]);
                    }
                    if (json["currency_code"] !== undefined) {
                        self.dskapiCheckoutCurrencyCode(json["currency_code"]);
                    }
                    if (json["dskapi_module_version"] !== undefined) {
                        self.dskapiCheckoutModuleVersion(
                            json["dskapi_module_version"]
                        );
                    }
                    self.dskapiloading(false);
                },
            });
        },

        /**
         * Gets grand total from quote
         */
        getGrandTotal: function () {
            var totals = quote.getTotals() && quote.getTotals();
            return totals && totals()["grand_total"]
                ? parseFloat(totals()["grand_total"])
                : 0;
        },

        /**
         * Calculates normalized price based on EUR mode and currency code
         */
        calculatePrice: function (eurMode, currencyCode, price) {
            var p = parseFloat(price);
            var result = p;
            switch (parseInt(eurMode)) {
                case 0:
                    result = p;
                    break;
                case 1:
                    if (currencyCode === "EUR") {
                        result = p * 1.95583;
                    }
                    break;
                case 2:
                    if (currencyCode === "BGN") {
                        result = p / 1.95583;
                    }
                    break;
            }
            return result;
        },

        /**
         * Detects if current user agent is mobile
         */
        isMobileUserAgent: function () {
            var ua = navigator.userAgent || "";
            var isMobile =
                /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(
                    ua
                ) ||
                /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(
                    ua.substr(0, 4)
                );
            return !!isMobile;
        },

        /**
         * Applies mobile or desktop CSS classes to popup container elements
         */
        applyMobileClasses: function (isMobile) {
            var container = document.getElementById(
                "dskapi-checkout-popup-container"
            );
            if (!container) return;

            var replace = function (desktopCls, mobileCls) {
                var elDesktop = container.querySelector("." + desktopCls);
                var elMobile = container.querySelector("." + mobileCls);
                if (isMobile && elDesktop) {
                    elDesktop.classList.remove(desktopCls);
                    elDesktop.classList.add(mobileCls);
                }
                if (!isMobile && elMobile) {
                    elMobile.classList.remove(mobileCls);
                    elMobile.classList.add(desktopCls);
                }
            };

            replace("dskapi_PopUp_Detailed_v1", "dskapim_PopUp_Detailed_v1");
            replace("dskapi_Mask", "dskapim_Mask");
            replace("dskapi_product_name", "dskapim_product_name");
            replace("dskapi_body_panel_txt3", "dskapim_body_panel_txt3");
            replace("dskapi_body_panel_txt4", "dskapim_body_panel_txt4");
            replace(
                "dskapi_body_panel_txt3_left",
                "dskapim_body_panel_txt3_left"
            );
            replace(
                "dskapi_body_panel_txt3_right",
                "dskapim_body_panel_txt3_right"
            );
            replace("dskapi_sumi_panel", "dskapim_sumi_panel");
            replace("dskapi_kredit_panel", "dskapim_kredit_panel");
            replace("dskapi_body_panel_footer", "dskapim_body_panel_footer");
            replace("dskapi_body_panel_left", "dskapim_body_panel_left");
        },

        /**
         * Opens the DSK API installment plans popup
         */
        openDskapiPopup: function () {
            var selfRef = this;
            var eur = parseInt(this.dskapiCheckoutEur() || 0);
            var currencyCode = this.dskapiCheckoutCurrencyCode() || "BGN";
            var price = this.getGrandTotal();
            var normalized = this.calculatePrice(eur, currencyCode, price);

            var dskapiCid = this.dskapiCheckoutCid() || "";
            var dskapiLive = this.dskapiCheckoutLiveUrl() || "";
            var dskapiProductId = this.dskapiCheckoutProductId() || 0;

            // Store config values
            this.dskapiCheckoutCid(dskapiCid);
            this.dskapiCheckoutLiveUrl(dskapiLive);
            this.dskapiCheckoutEur(eur);
            this.dskapiCheckoutCurrencyCode(currencyCode);

            var url =
                dskapiLive +
                "/function/getproduct.php?cid=" +
                encodeURIComponent(dskapiCid) +
                "&price=" +
                encodeURIComponent(normalized) +
                "&product_id=" +
                encodeURIComponent(dskapiProductId);

            $.ajax({ url: url, type: "GET", dataType: "json" })
                .done(function (resp) {
                    try {
                        var status = parseInt(resp.dsk_status || 0);
                        var min = parseFloat(resp.dsk_minstojnost || 0);
                        var max = parseFloat(resp.dsk_maxstojnost || 0);
                        var visibleMaskStr = String(
                            resp.dsk_vnoski_visible || "0"
                        );
                        var visibleMask = parseInt(visibleMaskStr);
                        var vnoskiDefault = parseInt(
                            resp.dsk_vnoski_default || 12
                        );

                        if (
                            status === 0 ||
                            normalized < min ||
                            normalized > max
                        ) {
                            alert(
                                "Сумата не е в допустимия диапазон за кредит."
                            );
                            return;
                        }

                        selfRef.dskapiCheckoutMaxstojnost(max);

                        // Calculate currency sign based on eur mode
                        var sign = "лв.";
                        switch (parseInt(eur)) {
                            case 0:
                                sign = "лв.";
                                break;
                            case 1:
                                sign = "лв.";
                                break;
                            case 2:
                                sign = "евро";
                                break;
                        }
                        selfRef.dskapiCheckoutSign(sign);

                        // Price - set observable
                        var priceDisplay = normalized.toFixed(2);
                        selfRef.dskapiCheckoutPrice(priceDisplay);

                        // Installment options - filtering based on visibility (bitmask logic)
                        var options = [];
                        for (var i = 3; i <= 48; i++) {
                            var bit = 1 << (i - 3);
                            var isVisibleInMask = (visibleMask & bit) !== 0;
                            var shouldShow =
                                isVisibleInMask || i === vnoskiDefault;

                            if (shouldShow) {
                                options.push({
                                    value: i,
                                    label: i + " месеца",
                                });
                            }
                        }

                        selfRef.dskapiCheckoutVnoskiOptions(options);
                        selfRef.dskapiCheckoutSelectedVnoski(vnoskiDefault);

                        // Set picture URL
                        var reklama = resp.dsk_reklama || 1;
                        var isMobile = selfRef.isMobileUserAgent();
                        var pictureUrl =
                            dskapiLive +
                            "/calculators/assets/img/" +
                            (isMobile ? "dskm" : "dsk") +
                            reklama +
                            ".png";
                        selfRef.dskapiCheckoutPicture(pictureUrl);

                        // Module version is already set from getCheckoutDskapi endpoint

                        // Apply mobile classes if needed and show popup
                        selfRef.applyMobileClasses(isMobile);
                        var container = document.getElementById(
                            "dskapi-checkout-popup-container"
                        );
                        if (container) container.style.display = "block";

                        selfRef.updateDskapiCheckoutInstallments();
                    } catch (e) {
                        alert(
                            "Възникна грешка при зареждане на погасителните планове."
                        );
                        console.error(e);
                    }
                })
                .fail(function () {
                    alert("Възникна грешка при връзка със сървъра на DSK.");
                });
        },

        /**
         * Closes the DSK API popup
         */
        closeDskapiCheckoutPopup: function () {
            var container = document.getElementById(
                "dskapi-checkout-popup-container"
            );
            if (container) container.style.display = "none";
        },

        /**
         * Updates installment calculation values based on selected number of installments
         */
        updateDskapiCheckoutInstallments: function () {
            var eur = parseInt(this.dskapiCheckoutEur() || 0);
            var currencyCode = this.dskapiCheckoutCurrencyCode() || "BGN";
            var dskapiCid = this.dskapiCheckoutCid() || "";
            var dskapiLive = this.dskapiCheckoutLiveUrl() || "";
            var dskapiProductId = this.dskapiCheckoutProductId() || 0;

            var vnoski = parseInt(this.dskapiCheckoutSelectedVnoski() || 12);

            var price = this.getGrandTotal();
            var normalized = this.calculatePrice(eur, currencyCode, price);

            var url =
                dskapiLive +
                "/function/getproductcustom.php?cid=" +
                encodeURIComponent(dskapiCid) +
                "&price=" +
                encodeURIComponent(normalized) +
                "&product_id=" +
                encodeURIComponent(dskapiProductId) +
                "&dskapi_vnoski=" +
                encodeURIComponent(vnoski);

            var selfRef = this;
            $.ajax({ url: url, type: "GET", dataType: "json" }).done(function (
                response
            ) {
                try {
                    var dsk_vnoska = parseFloat(response.dsk_vnoska);
                    var options = response.dsk_options;
                    var visible = response.dsk_is_visible;
                    var gpr = parseFloat(response.dsk_gpr || 0);

                    if (visible && options) {
                        var obshto_total = dsk_vnoska * vnoski;

                        selfRef.dskapiCheckoutVnoska(dsk_vnoska.toFixed(2));
                        selfRef.dskapiCheckoutObshtozaplashtane(
                            obshto_total.toFixed(2)
                        );
                        selfRef.dskapiCheckoutGpr(gpr.toFixed(2));
                        selfRef.oldDskapiCheckoutVnoski = vnoski;
                    } else {
                        alert(
                            "Избраният брой погасителни вноски е извън допустимия диапазон."
                        );
                        selfRef.dskapiCheckoutSelectedVnoski(
                            selfRef.oldDskapiCheckoutVnoski
                        );
                    }
                } catch (e) {
                    console.error("Error parsing DSK API response:", e);
                    alert("Възникна грешка при изчисляване на вноските.");
                }
            });
        },

        getOrderId: function () {
            var _url =
                "/dskapipayment/index/dskapigetid?tag=jLhrHYsfPQ3Gu9JgJPLJ";

            var param = "ajax=1";
            jQuery
                .ajax({
                    showLoader: true,
                    url: _url,
                    data: param,
                    type: "POST",
                    dataType: "json",
                })
                .done(function (data) {
                    if (parseInt(data.msg_status) == 1) {
                        if (parseInt(data.dskapi_send_mail) == 1) {
                            alert(
                                "Има временен проблем с комуникацията към DSK Credit. Изпратен е мейл с Вашата заявка към Банката. Моля очаквайте обратна връзка от Банката за да продължите процедурата по вашата заявка за кредит."
                            );
                            return;
                        } else {
                            var dskapireturn = data.dskapireturn;
                            window.location = dskapireturn;
                            return;
                        }
                    } else {
                        alert("Error get data!");
                    }
                })
                .fail(function (XMLHttpRequest, textStatus, errorThrown) {
                    alert("Error get data!");
                });
        },

        afterPlaceOrder: function () {
            this.getOrderId();
        },
    });
});
