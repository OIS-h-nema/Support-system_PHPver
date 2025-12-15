/**
 * ファイル名: master.js
 * 機能概要: マスタ設定用JavaScript
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 08）
 */

/**
 * マスタ管理オブジェクト
 */
var MasterManager = {
    
    // 現在のマスタ種別
    masterType: '',
    
    // 編集モード（'new' or 'edit'）
    currentMode: 'new',
    
    // 編集中のコード
    currentCode: null,
    currentBumonCode: null,
    
    /**
     * 初期化
     * @param {string} masterType マスタ種別
     */
    init: function(masterType) {
        this.masterType = masterType;
        this.currentMode = 'new';
        this.currentCode = null;
        this.currentBumonCode = null;
        
        this.bindEvents();
        this.loadList();
    },
    
    /**
     * イベントバインド
     */
    bindEvents: function() {
        var self = this;

        // 保存ボタン
        $('#btn-master-save').off('click').on('click', function() {
            self.save();
        });
        
        // クリアボタン
        $('#btn-master-clear').off('click').on('click', function() {
            self.clearForm();
        });

        // 閉じるボタン
        MasterManager.bindCloseButtons(function(e) {
            e.preventDefault();
            self.close();
        });
        
        // フィルタ変更
        $('#filter-bumon, #filter-use').off('change').on('change', function() {
            self.loadList();
        });
        
        // 編集ボタン
        $(document).off('click', '.btn-master-edit').on('click', '.btn-master-edit', function() {
            var $row = $(this).closest('tr');
            var code = $row.data('code');
            var bumonCode = $row.data('bumon-code');
            self.edit(code, bumonCode);
        });
        
        // 削除ボタン
        $(document).off('click', '.btn-master-delete').on('click', '.btn-master-delete', function() {
            var $row = $(this).closest('tr');
            var code = $row.data('code');
            var bumonCode = $row.data('bumon-code');
            var name = $row.data('name');
            self.deleteConfirm(code, bumonCode, name);
        });
        
        // Enterキーで保存
        $('.master-input-area input[type="text"], .master-input-area select').off('keydown').on('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                self.save();
            }
        });
        
        // ESCキーで閉じる
        $(document).off('keydown.master').on('keydown.master', function(e) {
            if (e.keyCode === 27) {
                self.close();
            }
        });
    },

    /**
     * 閉じるボタンのイベントをバインド（委譲で確実に捕捉）
     */
    bindCloseEvent: function() {
        var self = this;

        $(document).off('click.masterClose').on('click.masterClose', '#btn-master-close, .master-close-btn', function(e) {
            e.preventDefault();
            self.close();
        });
    },
    
    /**
     * 一覧読み込み
     */
    loadList: function() {
        var self = this;
        
        var params = {
            action: 'list',
            master_type: this.masterType
        };
        
        // フィルタ条件追加
        if ($('#filter-bumon').length > 0) {
            params.filter_bumon = $('#filter-bumon').val();
        }
        if ($('#filter-use').length > 0) {
            params.filter_use = $('#filter-use').val();
        }
        
        AppAjax.post('master_ajax.php', params, function(response) {
            if (response.status === 'success') {
                self.renderList(response.data);
            } else {
                AppMessage.showError(response.message || '一覧の取得に失敗しました。');
            }
        });
    },
    
    /**
     * 一覧描画
     * @param {array} data データ配列
     */
    renderList: function(data) {
        var html = '';
        var columnCount = $('#master-table-body').closest('table').find('thead th').length || 1;

        if (!data || data.length === 0) {
            html = '<tr><td colspan="' + columnCount + '" class="no-data">データがありません</td></tr>';
        } else {
            for (var i = 0; i < data.length; i++) {
                var row = data[i];
                html += this.renderRow(row);
            }
        }

        $('#master-table-body').html(html);
    },
    
    /**
     * 行描画（マスタ種別ごとにオーバーライド）
     * @param {object} row データ行
     * @return {string} HTML
     */
    renderRow: function(row) {
        // サブクラスで実装
        return '';
    },
    
    /**
     * 編集モードに切り替え
     * @param {number} code コード
     * @param {number} bumonCode 部門コード（商品・定型文の場合）
     */
    edit: function(code, bumonCode) {
        var self = this;
        
        var params = {
            action: 'get',
            master_type: this.masterType,
            code: code
        };
        
        if (bumonCode) {
            params.bumon_code = bumonCode;
        }
        
        AppAjax.post('master_ajax.php', params, function(response) {
            if (response.status === 'success') {
                self.setFormData(response.data);
                self.currentMode = 'edit';
                self.currentCode = code;
                self.currentBumonCode = bumonCode;
                self.updateFormState();
            } else {
                AppMessage.showError(response.message || 'データの取得に失敗しました。');
            }
        });
    },
    
    /**
     * フォームにデータをセット（サブクラスで実装）
     * @param {object} data データ
     */
    setFormData: function(data) {
        // サブクラスで実装
    },
    
    /**
     * フォーム状態更新
     */
    updateFormState: function() {
        if (this.currentMode === 'edit') {
            // 編集モード：コード入力不可
            $('#master-code').prop('readonly', true).addClass('readonly');
            if (this.masterType === 'product' || this.masterType === 'template') {
                $('#master-bumon').prop('disabled', true);
            }
        } else {
            // 新規モード：コード入力可
            if (this.masterType === 'content') {
                $('#master-code').prop('readonly', false).removeClass('readonly');
            }
            if (this.masterType === 'product' || this.masterType === 'template') {
                $('#master-bumon').prop('disabled', false);
            }
        }
    },
    
    /**
     * 保存処理
     */
    save: function() {
        var self = this;
        
        // 入力値取得
        var params = this.getFormData();
        params.action = 'save';
        params.master_type = this.masterType;
        params.mode = this.currentMode;
        
        if (this.currentMode === 'edit') {
            params.code = this.currentCode;
            if (this.currentBumonCode) {
                params.bumon_code = this.currentBumonCode;
            }
        }
        
        AppAjax.post('master_ajax.php', params, function(response) {
            if (response.status === 'success') {
                self.showMessage(response.message);
                self.clearForm();
                self.loadList();
            } else {
                if (response.errors && response.errors.length > 0) {
                    self.showErrors(response.errors);
                } else {
                    AppMessage.showError(response.message || '保存に失敗しました。');
                }
            }
        });
    },
    
    /**
     * フォームからデータを取得（サブクラスで実装）
     * @return {object} データ
     */
    getFormData: function() {
        // サブクラスで実装
        return {};
    },
    
    /**
     * 削除確認
     * @param {number} code コード
     * @param {number} bumonCode 部門コード
     * @param {string} name 名前
     */
    deleteConfirm: function(code, bumonCode, name) {
        var self = this;
        
        if (!confirm('"' + name + '" を削除してもよろしいですか？')) {
            return;
        }
        
        var params = {
            action: 'delete',
            master_type: this.masterType,
            code: code
        };
        
        if (bumonCode) {
            params.bumon_code = bumonCode;
        }
        
        AppAjax.post('master_ajax.php', params, function(response) {
            if (response.status === 'success') {
                self.showMessage(response.message);
                self.loadList();
            } else {
                AppMessage.showError(response.message || '削除に失敗しました。');
            }
        });
    },
    
    /**
     * フォームクリア
     */
    clearForm: function() {
        this.currentMode = 'new';
        this.currentCode = null;
        this.currentBumonCode = null;
        
        // 入力欄クリア
        $('.master-input-area input[type="text"]').val('');
        $('.master-input-area textarea').val('');
        $('.master-input-area select').prop('selectedIndex', 0);
        
        // 使用区分は「使用する」をデフォルト
        $('#master-use').val('1');
        
        this.updateFormState();
        this.clearMessages();
    },
    
    /**
     * 画面を閉じる
     */
    close: function() {
        // iframe内から親ウィンドウのモーダルを閉じる
        try {
            if (window.parent && window.parent !== window && typeof window.parent.closeMasterModal === 'function') {
                window.parent.closeMasterModal();
                return;
            }
        } catch (e) {
            // クロスドメインやアクセス権の問題で失敗した場合は後続のフォールバックへ
            console.warn('closeMasterModal call failed in parent context:', e);
        }

        // 直接ウィンドウを開いている場合
        if (window.opener && !window.opener.closed) {
            window.close();
        } else {
            // メイン画面に戻る
            window.location.href = 'support_main.php';
        }
    },
    
    /**
     * メッセージ表示
     * @param {string} message メッセージ
     */
    showMessage: function(message) {
        this.clearMessages();
        var html = '<div class="success-box"><p class="success-message">' + 
                   AppUtil.escapeHtml(message) + '</p></div>';
        $('.master-message-area').html(html);
    },
    
    /**
     * エラー表示
     * @param {array} errors エラーメッセージ配列
     */
    showErrors: function(errors) {
        this.clearMessages();
        var html = '<div class="error-box">';
        for (var i = 0; i < errors.length; i++) {
            html += '<p class="error-message"><span class="icon-error">！</span>' + 
                    AppUtil.escapeHtml(errors[i]) + '</p>';
        }
        html += '</div>';
        $('.master-message-area').html(html);
    },
    
    /**
     * メッセージクリア
     */
    clearMessages: function() {
        $('.master-message-area').empty();
    },

    /**
     * 閉じるボタンのバインド（安全側の一括管理）
     * @param {function} handler クリック時の処理
     */
    bindCloseButtons: function(handler) {
        var clickHandler = handler || function(e) {
            e.preventDefault();
            MasterManager.close();
        };

        $(document)
            .off('click.masterClose', '#btn-master-close, .master-close-btn')
            .on('click.masterClose', '#btn-master-close, .master-close-btn', clickHandler);
    }
};

/**
 * 商品マスタ管理
 */
var ProductMaster = $.extend({}, MasterManager, {
    
    renderRow: function(row) {
        var useLabel = row.use_flag == 1 ? '使用する' : '使用しない';
        var useClass = row.use_flag == 1 ? '' : 'text-muted';
        
        return '<tr data-code="' + row.code + '" data-bumon-code="' + row.bumon_code + '" data-name="' + AppUtil.escapeHtml(row.name) + '">' +
               '<td>' + AppUtil.escapeHtml(row.bumon_name) + '</td>' +
               '<td>' + row.code + '</td>' +
               '<td>' + AppUtil.escapeHtml(row.name) + '</td>' +
               '<td class="' + useClass + '">' + useLabel + '</td>' +
               '<td>' + AppUtil.escapeHtml(row.update_date) + '</td>' +
               '<td class="action-cell">' +
               '<button type="button" class="btn btn-sm btn-outline btn-master-edit">修正</button> ' +
               '<button type="button" class="btn btn-sm btn-danger btn-master-delete">削除</button>' +
               '</td></tr>';
    },
    
    setFormData: function(data) {
        $('#master-bumon').val(data.bumon_code);
        $('#master-code').val(data.code);
        $('#master-name').val(data.name);
        $('#master-use').val(data.use_flag);
    },
    
    getFormData: function() {
        return {
            bumon_code: $('#master-bumon').val(),
            name: $('#master-name').val(),
            use_flag: $('#master-use').val()
        };
    }
});

/**
 * 定型文マスタ管理
 */
var TemplateMaster = $.extend({}, MasterManager, {
    
    renderRow: function(row) {
        return '<tr data-code="' + row.code + '" data-bumon-code="' + row.bumon_code + '" data-name="定型文' + row.code + '">' +
               '<td>' + AppUtil.escapeHtml(row.bumon_name) + '</td>' +
               '<td>' + row.code + '</td>' +
               '<td class="text-cell">' + AppUtil.escapeHtml(row.display_text) + '</td>' +
               '<td>' + AppUtil.escapeHtml(row.update_date) + '</td>' +
               '<td class="action-cell">' +
               '<button type="button" class="btn btn-sm btn-outline btn-master-edit">修正</button> ' +
               '<button type="button" class="btn btn-sm btn-danger btn-master-delete">削除</button>' +
               '</td></tr>';
    },
    
    setFormData: function(data) {
        $('#master-bumon').val(data.bumon_code);
        $('#master-code').val(data.code);
        $('#master-text').val(data.text);
    },
    
    getFormData: function() {
        return {
            bumon_code: $('#master-bumon').val(),
            text: $('#master-text').val()
        };
    }
});

/**
 * 対応区分マスタ管理
 */
var CategoryMaster = $.extend({}, MasterManager, {
    
    renderRow: function(row) {
        return '<tr data-code="' + row.code + '" data-name="' + AppUtil.escapeHtml(row.name) + '">' +
               '<td>' + row.code + '</td>' +
               '<td>' + AppUtil.escapeHtml(row.name) + '</td>' +
               '<td>' + AppUtil.escapeHtml(row.update_date) + '</td>' +
               '<td class="action-cell">' +
               '<button type="button" class="btn btn-sm btn-outline btn-master-edit">修正</button> ' +
               '<button type="button" class="btn btn-sm btn-danger btn-master-delete">削除</button>' +
               '</td></tr>';
    },
    
    setFormData: function(data) {
        $('#master-code').val(data.code);
        $('#master-name').val(data.name);
    },
    
    getFormData: function() {
        return {
            name: $('#master-name').val()
        };
    }
});

/**
 * 対応内容項目マスタ管理
 */
var ContentMaster = $.extend({}, MasterManager, {
    
    renderRow: function(row) {
        return '<tr data-code="' + row.code + '" data-name="' + AppUtil.escapeHtml(row.name) + '">' +
               '<td>' + row.code + '</td>' +
               '<td>' + AppUtil.escapeHtml(row.name) + '</td>' +
               '<td>' + AppUtil.escapeHtml(row.update_date) + '</td>' +
               '<td class="action-cell">' +
               '<button type="button" class="btn btn-sm btn-outline btn-master-edit">修正</button> ' +
               '<button type="button" class="btn btn-sm btn-danger btn-master-delete">削除</button>' +
               '</td></tr>';
    },
    
    setFormData: function(data) {
        $('#master-code').val(data.code);
        $('#master-name').val(data.name);
    },
    
    getFormData: function() {
        return {
            code: $('#master-code').val(),
            name: $('#master-name').val()
        };
    },
    
    updateFormState: function() {
        if (this.currentMode === 'edit') {
            // 編集モード：コード入力不可
            $('#master-code').prop('readonly', true).addClass('readonly');
        } else {
            // 新規モード：コード入力可
            $('#master-code').prop('readonly', false).removeClass('readonly');
        }
    }
});

// 予期しない初期化失敗時でも閉じるボタンが効くように、共通バインドを初期ロードで実行
$(function() {
    MasterManager.bindCloseButtons();
});
