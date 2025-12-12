/**
 * ファイル名: common.js
 * 機能概要: 共通JavaScript
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 05）
 */

/**
 * 共通ユーティリティ
 */
var AppUtil = {
    
    /**
     * HTMLエスケープ
     * @param {string} str 対象文字列
     * @return {string} エスケープ済み文字列
     */
    escapeHtml: function(str) {
        if (str === null || str === undefined) {
            return '';
        }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },
    
    /**
     * 日付フォーマット
     * @param {string|Date} date 日付
     * @param {string} format フォーマット（Y-m-d, Y/m/d等）
     * @return {string} フォーマット済み日付
     */
    formatDate: function(date, format) {
        if (!date) return '';
        
        var d = (date instanceof Date) ? date : new Date(date);
        if (isNaN(d.getTime())) return '';
        
        var year = d.getFullYear();
        var month = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        
        format = format || 'Y-m-d';
        return format
            .replace('Y', year)
            .replace('m', month)
            .replace('d', day);
    },
    
    /**
     * 数値フォーマット（カンマ区切り）
     * @param {number} num 数値
     * @return {string} フォーマット済み数値
     */
    formatNumber: function(num) {
        if (num === null || num === undefined || num === '') {
            return '';
        }
        return Number(num).toLocaleString();
    },
    
    /**
     * 空チェック
     * @param {*} value 値
     * @return {boolean}
     */
    isEmpty: function(value) {
        return value === null || value === undefined || value === '';
    }
};

/**
 * Ajax通信ラッパー
 */
var AppAjax = {
    
    /**
     * POST送信
     * @param {string} url URL
     * @param {object} data データ
     * @param {function} successCallback 成功時コールバック
     * @param {function} errorCallback エラー時コールバック
     */
    post: function(url, data, successCallback, errorCallback) {
        this.request('POST', url, data, successCallback, errorCallback);
    },
    
    /**
     * GET送信
     * @param {string} url URL
     * @param {object} data データ
     * @param {function} successCallback 成功時コールバック
     * @param {function} errorCallback エラー時コールバック
     */
    get: function(url, data, successCallback, errorCallback) {
        this.request('GET', url, data, successCallback, errorCallback);
    },
    
    /**
     * リクエスト送信
     * @param {string} method メソッド
     * @param {string} url URL
     * @param {object} data データ
     * @param {function} successCallback 成功時コールバック
     * @param {function} errorCallback エラー時コールバック
     */
    request: function(method, url, data, successCallback, errorCallback) {
        // ローディング表示
        AppLoading.show();
        
        $.ajax({
            type: method,
            url: url,
            dataType: 'json',
            data: data,
            success: function(response) {
                AppLoading.hide();
                
                // セッション切れチェック
                if (response.status === 'error' && response.redirect) {
                    alert(response.message);
                    window.location.href = response.redirect;
                    return;
                }
                
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            },
            error: function(xhr, status, error) {
                AppLoading.hide();
                
                // セッション切れ
                if (xhr.status === 401) {
                    alert('セッションが切れました。再度ログインしてください。');
                    window.location.href = 'login.php';
                    return;
                }
                
                var message = '通信エラーが発生しました。';
                if (xhr.status === 403) {
                    message = 'アクセス権限がありません。';
                } else if (xhr.status === 404) {
                    message = '指定されたページが見つかりません。';
                } else if (xhr.status === 500) {
                    message = 'サーバーエラーが発生しました。';
                }
                
                if (typeof errorCallback === 'function') {
                    errorCallback(message, xhr);
                } else {
                    AppMessage.showError(message);
                }
            }
        });
    }
};

/**
 * メッセージ表示
 */
var AppMessage = {
    
    /**
     * エラーメッセージ表示
     * @param {string|array} message メッセージ
     */
    showError: function(message) {
        this.clearAll();
        
        var messages = Array.isArray(message) ? message : [message];
        var html = '<div class="error-box">';
        
        for (var i = 0; i < messages.length; i++) {
            html += '<p class="error-message"><span class="icon-error">！</span>' + 
                    AppUtil.escapeHtml(messages[i]) + '</p>';
        }
        html += '</div>';
        
        $('.main-content').prepend(html);
        this.scrollToTop();
    },
    
    /**
     * 成功メッセージ表示
     * @param {string} message メッセージ
     */
    showSuccess: function(message) {
        this.clearAll();
        
        var html = '<div class="success-box"><p class="success-message">' + 
                   AppUtil.escapeHtml(message) + '</p></div>';
        
        $('.main-content').prepend(html);
        this.scrollToTop();
    },
    
    /**
     * 警告メッセージ表示
     * @param {string} message メッセージ
     */
    showWarning: function(message) {
        this.clearAll();
        
        var html = '<div class="warning-box"><p class="warning-message">' + 
                   AppUtil.escapeHtml(message) + '</p></div>';
        
        $('.main-content').prepend(html);
        this.scrollToTop();
    },
    
    /**
     * フィールドエラー表示
     * @param {object} errors フィールド名をキーとしたエラーオブジェクト
     */
    showFieldErrors: function(errors) {
        // 既存のエラー状態をクリア
        $('.input-error').removeClass('input-error');
        $('.field-error-message').remove();
        
        // フィールドごとにエラー表示
        for (var field in errors) {
            if (errors.hasOwnProperty(field)) {
                var $input = $('[name="' + field + '"]');
                $input.addClass('input-error');
                $input.after('<span class="field-error-message">' + 
                            AppUtil.escapeHtml(errors[field]) + '</span>');
            }
        }
    },
    
    /**
     * メッセージクリア
     */
    clearAll: function() {
        $('.error-box, .success-box, .warning-box, .info-box').remove();
        $('.input-error').removeClass('input-error');
        $('.field-error-message').remove();
    },
    
    /**
     * ページ上部にスクロール
     */
    scrollToTop: function() {
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
};

/**
 * ローディング表示
 */
var AppLoading = {
    
    $overlay: null,
    
    /**
     * 初期化
     */
    init: function() {
        if ($('.loading-overlay').length === 0) {
            var html = '<div class="loading-overlay">' +
                       '<div class="loading-spinner"></div></div>';
            $('body').append(html);
        }
        this.$overlay = $('.loading-overlay');
    },
    
    /**
     * 表示
     */
    show: function() {
        if (!this.$overlay) {
            this.init();
        }
        this.$overlay.addClass('active');
    },
    
    /**
     * 非表示
     */
    hide: function() {
        if (this.$overlay) {
            this.$overlay.removeClass('active');
        }
    }
};

/**
 * モーダル管理
 */
var AppModal = {
    
    /**
     * モーダル表示
     * @param {string} modalId モーダルID
     */
    show: function(modalId) {
        var $modal = $('#' + modalId);
        if ($modal.length > 0) {
            $modal.addClass('active');
            $('body').css('overflow', 'hidden');
        }
    },
    
    /**
     * モーダル非表示
     * @param {string} modalId モーダルID
     */
    hide: function(modalId) {
        var $modal = $('#' + modalId);
        if ($modal.length > 0) {
            $modal.removeClass('active');
            $('body').css('overflow', '');
        }
    },
    
    /**
     * 全モーダル非表示
     */
    hideAll: function() {
        $('.modal-overlay').removeClass('active');
        $('body').css('overflow', '');
    }
};

/**
 * 確認ダイアログ
 */
var AppConfirm = {
    
    /**
     * 確認ダイアログ表示
     * @param {string} message メッセージ
     * @param {function} callback OKクリック時のコールバック
     */
    show: function(message, callback) {
        if (confirm(message)) {
            if (typeof callback === 'function') {
                callback();
            }
            return true;
        }
        return false;
    }
};

/**
 * フォームヘルパー
 */
var AppForm = {
    
    /**
     * フォームデータをオブジェクトとして取得
     * @param {string} formSelector フォームセレクタ
     * @return {object}
     */
    getData: function(formSelector) {
        var $form = $(formSelector);
        var data = {};
        
        $form.find('input, select, textarea').each(function() {
            var $el = $(this);
            var name = $el.attr('name');
            
            if (!name) return;
            
            if ($el.is(':checkbox')) {
                if ($el.is(':checked')) {
                    if (data[name]) {
                        if (!Array.isArray(data[name])) {
                            data[name] = [data[name]];
                        }
                        data[name].push($el.val());
                    } else {
                        data[name] = $el.val();
                    }
                }
            } else if ($el.is(':radio')) {
                if ($el.is(':checked')) {
                    data[name] = $el.val();
                }
            } else {
                data[name] = $el.val();
            }
        });
        
        return data;
    },
    
    /**
     * フォームリセット
     * @param {string} formSelector フォームセレクタ
     */
    reset: function(formSelector) {
        $(formSelector)[0].reset();
        AppMessage.clearAll();
    },
    
    /**
     * フォームの変更状態チェック
     * @param {string} formSelector フォームセレクタ
     * @return {boolean}
     */
    isChanged: function(formSelector) {
        var $form = $(formSelector);
        var changed = false;
        
        $form.find('input, select, textarea').each(function() {
            if (this.defaultValue !== undefined && this.value !== this.defaultValue) {
                changed = true;
                return false;
            }
        });
        
        return changed;
    }
};

/**
 * 初期化処理
 */
$(function() {
    
    // ローディング初期化
    AppLoading.init();
    
    // モーダル背景クリックで閉じる
    $(document).on('click', '.modal-overlay', function(e) {
        if ($(e.target).hasClass('modal-overlay')) {
            AppModal.hideAll();
        }
    });
    
    // モーダル閉じるボタン
    $(document).on('click', '.modal-close', function() {
        AppModal.hideAll();
    });
    
    // Enterキーでフォーム送信を防止（必要な場合）
    $(document).on('keydown', '.prevent-enter', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });
    
    // 数値入力のみ許可
    $(document).on('input', '.numeric-only', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // ページ離脱確認（フォーム変更時）
    var formChanged = false;
    
    $(document).on('change input', '.watch-change', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return '入力内容が保存されていません。このページを離れますか？';
        }
    });
    
    // フォーム送信時は確認を解除
    $(document).on('submit', 'form', function() {
        formChanged = false;
    });
});
