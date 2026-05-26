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
    dskapiCheckoutPictureDesktop: ko.observable(""),
    dskapiCheckoutPictureMobile: ko.observable(""),
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
    dskapiCheckoutGetProductUrl: ko.observable(""),
    dskapiCheckoutGetProductCustomUrl: ko.observable(""),
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
        self.oldDskapiCheckoutVnoski = self.dskapiCheckoutSelectedVnoski();
      };

      this.onDskapiCheckoutVnoskiChange = function (data, event) {
        self.updateDskapiCheckoutInstallments();
      };

      // Auto-select this payment method if URL parameter or sessionStorage is present
      var urlParams = new URLSearchParams(window.location.search);
      var autoSelectPaymentMethod = sessionStorage.getItem(
        "autoSelectPaymentMethod",
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
          if (json["dskapi_getproduct_url"] !== undefined) {
            self.dskapiCheckoutGetProductUrl(json["dskapi_getproduct_url"]);
          }
          if (json["dskapi_getproductcustom_url"] !== undefined) {
            self.dskapiCheckoutGetProductCustomUrl(
              json["dskapi_getproductcustom_url"],
            );
          }
          if (json["dskapi_eur"] !== undefined) {
            self.dskapiCheckoutEur(json["dskapi_eur"]);
          }
          if (json["currency_code"] !== undefined) {
            self.dskapiCheckoutCurrencyCode(json["currency_code"]);
          }
          if (json["dskapi_module_version"] !== undefined) {
            self.dskapiCheckoutModuleVersion(json["dskapi_module_version"]);
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

    buildPopupPictureUrls: function (dskapiLive, reklama) {
      var banner = reklama || 1;
      var base = (dskapiLive || "").replace(/\/$/, "");

      return {
        desktop: base + "/calculators/assets/img/dsk" + banner + ".png",
        mobile: base + "/calculators/assets/img/dskm" + banner + ".png",
      };
    },

    buildGetProductUrl: function (dskapiCid, normalized, dskapiProductId) {
      var baseUrl = this.dskapiCheckoutGetProductUrl() || "";
      var params =
        "cid=" +
        encodeURIComponent(dskapiCid) +
        "&price=" +
        encodeURIComponent(normalized) +
        "&product_id=" +
        encodeURIComponent(dskapiProductId);

      if (baseUrl) {
        return baseUrl + (baseUrl.indexOf("?") >= 0 ? "&" : "?") + params;
      }

      var dskapiLive = this.dskapiCheckoutLiveUrl() || "";
      return dskapiLive + "/function/getproduct.php?" + params;
    },

    buildGetProductCustomUrl: function (
      dskapiCid,
      normalized,
      dskapiProductId,
      vnoski,
    ) {
      var baseUrl = this.dskapiCheckoutGetProductCustomUrl() || "";
      var params =
        "cid=" +
        encodeURIComponent(dskapiCid) +
        "&price=" +
        encodeURIComponent(normalized) +
        "&product_id=" +
        encodeURIComponent(dskapiProductId) +
        "&dskapi_vnoski=" +
        encodeURIComponent(vnoski);

      if (baseUrl) {
        return baseUrl + (baseUrl.indexOf("?") >= 0 ? "&" : "?") + params;
      }

      var dskapiLive = this.dskapiCheckoutLiveUrl() || "";
      return dskapiLive + "/function/getproductcustom.php?" + params;
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

      var url = selfRef.buildGetProductUrl(
        dskapiCid,
        normalized,
        dskapiProductId,
      );

      $.ajax({ url: url, type: "GET", dataType: "json" })
        .done(function (resp) {
          try {
            var status = parseInt(resp.dsk_status || 0);
            var min = parseFloat(resp.dsk_minstojnost || 0);
            var max = parseFloat(resp.dsk_maxstojnost || 0);
            var visibleMaskStr = String(resp.dsk_vnoski_visible || "0");
            var visibleMask = parseInt(visibleMaskStr);
            var vnoskiDefault = parseInt(resp.dsk_vnoski_default || 12);

            if (status === 0 || normalized < min || normalized > max) {
              alert("Сумата не е в допустимия диапазон за кредит.");
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
              var shouldShow = isVisibleInMask || i === vnoskiDefault;

              if (shouldShow) {
                options.push({
                  value: i,
                  label: i + " месеца",
                });
              }
            }

            selfRef.dskapiCheckoutVnoskiOptions(options);
            selfRef.dskapiCheckoutSelectedVnoski(vnoskiDefault);

            var reklama = resp.dsk_reklama || 1;
            var pictureUrls = selfRef.buildPopupPictureUrls(
              dskapiLive,
              reklama,
            );
            selfRef.dskapiCheckoutPictureDesktop(pictureUrls.desktop);
            selfRef.dskapiCheckoutPictureMobile(pictureUrls.mobile);

            var container = document.getElementById(
              "dskapi-checkout-popup-container",
            );
            if (container) container.style.display = "block";

            selfRef.updateDskapiCheckoutInstallments();
          } catch (e) {
            alert("Възникна грешка при зареждане на погасителните планове.");
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
        "dskapi-checkout-popup-container",
      );
      if (container) container.style.display = "none";
    },

    /**
     * Updates installment calculation values based on selected number of installments
     */
    updateDskapiCheckoutInstallments: function () {
      var selfRef = this;
      var eur = parseInt(this.dskapiCheckoutEur() || 0);
      var currencyCode = this.dskapiCheckoutCurrencyCode() || "BGN";
      var dskapiCid = this.dskapiCheckoutCid() || "";
      var dskapiProductId = this.dskapiCheckoutProductId() || 0;

      var vnoski = parseInt(this.dskapiCheckoutSelectedVnoski() || 12);

      var price = this.getGrandTotal();
      var normalized = this.calculatePrice(eur, currencyCode, price);

      var url = selfRef.buildGetProductCustomUrl(
        dskapiCid,
        normalized,
        dskapiProductId,
        vnoski,
      );
      $.ajax({ url: url, type: "GET", dataType: "json" }).done(
        function (response) {
          try {
            var dsk_vnoska = parseFloat(response.dsk_vnoska);
            var options = response.dsk_options;
            var visible = response.dsk_is_visible;
            var gpr = parseFloat(response.dsk_gpr || 0);

            if (visible && options) {
              var obshto_total = dsk_vnoska * vnoski;

              selfRef.dskapiCheckoutVnoska(dsk_vnoska.toFixed(2));
              selfRef.dskapiCheckoutObshtozaplashtane(obshto_total.toFixed(2));
              selfRef.dskapiCheckoutGpr(gpr.toFixed(2));
              selfRef.oldDskapiCheckoutVnoski = vnoski;
            } else {
              alert(
                "Избраният брой погасителни вноски е извън допустимия диапазон.",
              );
              selfRef.dskapiCheckoutSelectedVnoski(
                selfRef.oldDskapiCheckoutVnoski,
              );
            }
          } catch (e) {
            console.error("Error parsing DSK API response:", e);
            alert("Възникна грешка при изчисляване на вноските.");
          }
        },
      );
    },

    getOrderId: function () {
      var _url = "/dskapipayment/index/dskapigetid?tag=jLhrHYsfPQ3Gu9JgJPLJ";

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
                "Има временен проблем с комуникацията към DSK Credit. Изпратен е мейл с Вашата заявка към Банката. Моля очаквайте обратна връзка от Банката за да продължите процедурата по вашата заявка за кредит.",
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
