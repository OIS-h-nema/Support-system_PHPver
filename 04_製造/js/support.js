/**
 * ファイル名: support.js
 * 機能概要: サポート報告書用JavaScript
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 05）
 */

/**
 * サポート報告書モジュール
 */
var Support = {
    
    /**
     * 初期化
     */
    init: function() {
        this.bindEvents();
    },
    
    /**
     * イベントバインド
     */
    bindEvents: function() {
        var self = this;
        
        // 検索フォーム
        $(document).on('submit', '#search-form', function(e) {
            e.preventDefault();
            self.search();
        });
        
        // 検索条件クリア
        $(document).on('click', '#btn-clear-search', function() {
            self.clearSearch();
        });
        
        // 表示件数変更
        $(document).on('change', '#display-count', function() {
            self.changeDisplayCount($(this).val());
        });
        
        // ソート
        $(document).on('click', '.sortable', function() {
            var column = $(this).data('column');
            var order = $(this).hasClass('asc') ? 'DESC' : 'ASC';
            self.sort(column, order);
        });
        
        // 新規登録
        $(document).on('click', '#btn-new', function() {
            self.openInputDialog(null);
        });
        
        // 編集
        $(document).on('click', '.btn-edit', function() {
            var seqno = $(this).data('seqno');
            self.openInputDialog(seqno);
        });
        
        // 削除
        $(document).on('click', '.btn-delete', function() {
            var seqno = $(this).data('seqno');
            self.confirmDelete(seqno);
        });
        
        // 入力ダイアログ保存
        $(document).on('click', '#btn-save', function() {
            self.save();
        });
        
        // 商品選択
        $(document).on('click', '.btn-select-product', function() {
            var index = $(this).data('index');
            self.openProductDialog(index);
        });
        
        // 定型文選択
        $(document).on('click', '#btn-select-template', function() {
            self.openTemplateDialog();
        });
    },
    
    /**
     * 検索実行
     */
    search: function() {
        var data = AppForm.getData('#search-form');
        data.action = 'search';
        data.page = 1;
        
        AppAjax.post('support_ajax01.php', data, function(response) {
            if (response.status === 'success') {
                Support.updateList(response.data);
            } else {
                AppMessage.showError(response.message);
            }
        });
    },
    
    /**
     * 検索条件クリア
     */
    clearSearch: function() {
        AppForm.reset('#search-form');
        this.search();
    },
    
    /**
     * 表示件数変更
     * @param {number} count 表示件数
     */
    changeDisplayCount: function(count) {
        var data = {
            action: 'change_display_count',
            count: count
        };
        
        AppAjax.post('support_ajax01.php', data, function(response) {
            if (response.status === 'success') {
                Support.search();
            }
        });
    },
    
    /**
     * ソート
     * @param {string} column カラム名
     * @param {string} order 並び順
     */
    sort: function(column, order) {
        var data = {
            action: 'sort',
            column: column,
            order: order
        };
        
        AppAjax.post('support_ajax01.php', data, function(response) {
            if (response.status === 'success') {
                Support.search();
            }
        });
    },
    
    /**
     * ページ移動
     * @param {number} page ページ番号
     */
    goToPage: function(page) {
        var data = AppForm.getData('#search-form');
        data.action = 'search';
        data.page = page;
        
        AppAjax.post('support_ajax01.php', data, function(response) {
            if (response.status === 'success') {
                Support.updateList(response.data);
            }
        });
    },
    
    /**
     * 一覧更新
     * @param {object} data レスポンスデータ
     */
    updateList: function(data) {
        // 一覧テーブル更新
        var html = '';
        
        if (data.list && data.list.length > 0) {
            for (var i = 0; i < data.list.length; i++) {
                var row = data.list[i];
                html += this.renderListRow(row);
            }
        } else {
            html = '<tr><td colspan="8" class="text-center">データがありません。</td></tr>';
        }
        
        $('#report-list tbody').html(html);
        
        // 件数・ページング更新
        if (data.paging) {
            this.updatePaging(data.paging);
        }
    },
    
    /**
     * 一覧行レンダリング
     * @param {object} row 行データ
     * @return {string} HTML
     */
    renderListRow: function(row) {
        var html = '<tr>';
        html += '<td>' + AppUtil.escapeHtml(row['SEQNO']) + '</td>';
        html += '<td>' + AppUtil.escapeHtml(row['対応開始日時']) + '</td>';
        html += '<td>' + AppUtil.escapeHtml(row['顧客名']) + '</td>';
        html += '<td>' + AppUtil.escapeHtml(row['作業担当名']) + '</td>';
        html += '<td>' + AppUtil.escapeHtml(row['対応区分名']) + '</td>';
        html += '<td class="text-center">';
        html += '<button type="button" class="btn btn-sm btn-outline btn-edit" data-seqno="' + row['SEQNO'] + '">編集</button>';
        if (row['can_delete']) {
            html += ' <button type="button" class="btn btn-sm btn-danger btn-delete" data-seqno="' + row['SEQNO'] + '">削除</button>';
        }
        html += '</td>';
        html += '</tr>';
        return html;
    },
    
    /**
     * ページング更新
     * @param {object} paging ページング情報
     */
    updatePaging: function(paging) {
        // 件数表示
        $('.result-count strong').text(paging.total);
        
        // ページネーション
        var html = '';
        
        if (paging.has_prev) {
            html += '<a href="#" onclick="Support.goToPage(' + (paging.current_page - 1) + '); return false;">前へ</a>';
        } else {
            html += '<span class="disabled">前へ</span>';
        }
        
        // ページ番号
        for (var i = 1; i <= paging.total_pages; i++) {
            if (i === paging.current_page) {
                html += '<span class="current">' + i + '</span>';
            } else {
                html += '<a href="#" onclick="Support.goToPage(' + i + '); return false;">' + i + '</a>';
            }
        }
        
        if (paging.has_next) {
            html += '<a href="#" onclick="Support.goToPage(' + (paging.current_page + 1) + '); return false;">次へ</a>';
        } else {
            html += '<span class="disabled">次へ</span>';
        }
        
        $('.pagination').html(html);
    },
    
    /**
     * 入力ダイアログを開く
     * @param {number|null} seqno SEQNO（新規時はnull）
     */
    openInputDialog: function(seqno) {
        if (seqno) {
            // 編集モード - データ取得
            var data = {
                action: 'get',
                seqno: seqno
            };
            
            AppAjax.post('support_ajax01.php', data, function(response) {
                if (response.status === 'success') {
                    Support.showInputDialog(response.data);
                } else {
                    AppMessage.showError(response.message);
                }
            });
        } else {
            // 新規モード
            this.showInputDialog(null);
        }
    },
    
    /**
     * 入力ダイアログ表示
     * @param {object|null} data データ（新規時はnull）
     */
    showInputDialog: function(data) {
        // フォームリセット
        AppForm.reset('#input-form');
        AppMessage.clearAll();
        
        if (data) {
            // データ設定
            $('#input-seqno').val(data['SEQNO']);
            $('#input-taiou-date').val(data['対応日']);
            $('#input-kokyaku-code').val(data['顧客コード']);
            $('#input-kokyaku-name').val(data['顧客名']);
            // 他フィールドも設定
        } else {
            // 初期値設定
            var today = new Date();
            $('#input-taiou-date').val(AppUtil.formatDate(today));
        }
        
        AppModal.show('input-modal');
    },
    
    /**
     * 保存
     */
    save: function() {
        var data = AppForm.getData('#input-form');
        data.action = data.seqno ? 'update' : 'insert';
        
        AppAjax.post('support_ajax01.php', data, function(response) {
            if (response.status === 'success') {
                AppModal.hide('input-modal');
                AppMessage.showSuccess(response.message);
                Support.search();
            } else if (response.errors) {
                AppMessage.showFieldErrors(response.errors);
            } else {
                AppMessage.showError(response.message);
            }
        });
    },
    
    /**
     * 削除確認
     * @param {number} seqno SEQNO
     */
    confirmDelete: function(seqno) {
        AppConfirm.show('この報告書を削除しますか？', function() {
            Support.deleteReport(seqno);
        });
    },
    
    /**
     * 削除実行
     * @param {number} seqno SEQNO
     */
    deleteReport: function(seqno) {
        var data = {
            action: 'delete',
            seqno: seqno
        };
        
        AppAjax.post('support_ajax01.php', data, function(response) {
            if (response.status === 'success') {
                AppMessage.showSuccess(response.message);
                Support.search();
            } else {
                AppMessage.showError(response.message);
            }
        });
    },
    
    /**
     * 商品選択ダイアログを開く
     * @param {number} index 商品インデックス（1-3）
     */
    openProductDialog: function(index) {
        $('#product-index').val(index);
        $('#product-keyword').val('');
        $('#product-list').html('<tr><td colspan="3" class="text-center">検索キーワードを入力してください</td></tr>');
        AppModal.show('product-modal');
    },
    
    /**
     * 商品検索
     */
    searchProducts: function() {
        var keyword = $('#product-keyword').val();
        
        if (!keyword) {
            AppMessage.showWarning('検索キーワードを入力してください。');
            return;
        }
        
        var data = {
            action: 'search_products',
            keyword: keyword
        };
        
        AppAjax.post('support_ajax01.php', data, function(response) {
            if (response.status === 'success') {
                Support.renderProductList(response.data);
            } else {
                AppMessage.showError(response.message);
            }
        });
    },
    
    /**
     * 商品一覧レンダリング
     * @param {array} products 商品リスト
     */
    renderProductList: function(products) {
        var html = '';
        
        if (products && products.length > 0) {
            for (var i = 0; i < products.length; i++) {
                var p = products[i];
                html += '<tr>';
                html += '<td>' + AppUtil.escapeHtml(p['商品コード']) + '</td>';
                html += '<td>' + AppUtil.escapeHtml(p['商品名']) + '</td>';
                html += '<td class="text-center">';
                html += '<button type="button" class="btn btn-sm btn-primary" onclick="Support.selectProduct(' + 
                        p['商品コード'] + ', \'' + AppUtil.escapeHtml(p['商品名']) + '\')">選択</button>';
                html += '</td>';
                html += '</tr>';
            }
        } else {
            html = '<tr><td colspan="3" class="text-center">該当する商品がありません。</td></tr>';
        }
        
        $('#product-list').html(html);
    },
    
    /**
     * 商品選択
     * @param {number} code 商品コード
     * @param {string} name 商品名
     */
    selectProduct: function(code, name) {
        var index = $('#product-index').val();
        $('#input-shohin-code' + index).val(code);
        $('#input-shohin-name' + index).val(name);
        AppModal.hide('product-modal');
    },
    
    /**
     * 定型文選択ダイアログを開く
     */
    openTemplateDialog: function() {
        var data = {
            action: 'get_templates'
        };
        
        AppAjax.post('support_ajax01.php', data, function(response) {
            if (response.status === 'success') {
                Support.renderTemplateList(response.data);
                AppModal.show('template-modal');
            } else {
                AppMessage.showError(response.message);
            }
        });
    },
    
    /**
     * 定型文一覧レンダリング
     * @param {array} templates 定型文リスト
     */
    renderTemplateList: function(templates) {
        var html = '';
        
        if (templates && templates.length > 0) {
            for (var i = 0; i < templates.length; i++) {
                var t = templates[i];
                html += '<tr>';
                html += '<td>' + AppUtil.escapeHtml(t['項目']) + '</td>';
                html += '<td class="text-center">';
                html += '<button type="button" class="btn btn-sm btn-primary" onclick="Support.selectTemplate(\'' + 
                        AppUtil.escapeHtml(t['定型文']) + '\')">挿入</button>';
                html += '</td>';
                html += '</tr>';
            }
        } else {
            html = '<tr><td colspan="2" class="text-center">定型文がありません。</td></tr>';
        }
        
        $('#template-list').html(html);
    },
    
    /**
     * 定型文選択
     * @param {string} text 定型文テキスト
     */
    selectTemplate: function(text) {
        var $textarea = $('#input-houkoku');
        var current = $textarea.val();
        var cursorPos = $textarea[0].selectionStart;
        
        var newText = current.substring(0, cursorPos) + text + current.substring(cursorPos);
        $textarea.val(newText);
        
        AppModal.hide('template-modal');
        $textarea.focus();
    }
};

/**
 * 初期化
 */
$(function() {
    Support.init();
});
