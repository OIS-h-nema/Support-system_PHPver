/**
 * ファイル名: support_dialog.js
 * 機能概要: サポート報告書入力ダイアログ用JavaScript
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 07）
 * 2025-12-10 キャンセルボタンのイベントをmousedownに変更（フォーカス問題対応）
 */

/**
 * 入力ダイアログ管理オブジェクト
 */
var InputDialog = {
    
    // ダイアログの状態
    mode: 'new',        // 'new' or 'edit'
    currentSeqno: null,
    originalData: {},   // 変更検出用
    formChanged: false,
    selectedKokyaku: null,
    
    /**
     * 初期化
     */
    init: function() {
        var self = this;
        
        // 既存のイベントハンドラを解除（重複登録防止）
        $('#btn-new').off('click');
        $('#btn-dialog-close, #btn-cancel').off('click mousedown');
        $('#input-dialog-overlay').off('mousedown');
        $(document).off('keydown.inputDialog');
        $('#btn-save').off('click');
        $('#btn-delete').off('click');
        
        // ダイアログを開くボタン（新規）
        $('#btn-new').on('click', function(e) {
            e.preventDefault();
            self.openNew();
        });
        
        // ダイアログを閉じるボタン（mousedownを使用してフォーカス問題を回避）
        $('#btn-dialog-close, #btn-cancel').on('mousedown', function(e) {
            e.stopPropagation();
            e.preventDefault();
            self.close();
        });
        
        // オーバーレイクリックで閉じる（オーバーレイ自体のクリックのみ、mousedownを使用）
        $('#input-dialog-overlay').on('mousedown', function(e) {
            // クリックされた要素がオーバーレイ自体の場合のみ閉じる
            if (e.target === this) {
                self.close();
            }
        });
        
        // Escキーで閉じる
        $(document).on('keydown.inputDialog', function(e) {
            if (e.keyCode === 27) { // ESC
                if ($('#template-search-overlay').hasClass('active')) {
                    TemplateDialog.close();
                } else if ($('#kokyaku-search-overlay').hasClass('active')) {
                    KokyakuSearchDialog.close();
                } else if ($('#input-dialog-overlay').hasClass('active')) {
                    self.close();
                }
            }
        });
        
        // 保存ボタン（デリゲートを使用）
        $(document).off('click', '#btn-save').on('click', '#btn-save', function(e) {
            e.preventDefault();
            e.stopPropagation();
            InputDialog.save();
        });

        // 削除ボタン（デリゲートを使用）
        $(document).off('click', '#btn-delete').on('click', '#btn-delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            InputDialog.confirmDelete();
        });
        
        // 顧客コードフォーカスアウト時
        $('#kokyaku-code').on('blur', function() {
            var code = $(this).val();
            if (code !== '') {
                self.fetchKokyakuName(code);
            } else {
                $('#kokyaku-name-display').text('');
                $('#kokyaku-name').val('');
            }
        });
        
        // 顧客検索ボタン
        $('#btn-kokyaku-search').on('click', function() {
            KokyakuSearchDialog.open();
        });
        
        // 定型文ボタン
        $('#btn-template').on('click', function() {
            TemplateDialog.open();
        });
        
        // フォーム変更検出
        $('#input-form input, #input-form select, #input-form textarea').on('change input', function() {
            self.formChanged = true;
        });
        
        // 数値入力のみ許可
        $('#kokyaku-code').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // 商品重複チェック
        $('#shohin-code1, #shohin-code2, #shohin-code3').on('change', function() {
            self.checkProductDuplicate();
        });
    },
    
    /**
     * 新規登録モードで開く
     */
    openNew: function() {
        this.mode = 'new';
        this.currentSeqno = null;
        this.formChanged = false;

        // フォームリセット
        this.resetForm();

        // タイトル設定
        $('#dialog-title').text('サポート報告書 新規登録');

        // ボタン表示制御
        this.setDeleteButtonVisibility(false);
        $('#btn-save').text('登録');
        
        // 更新日時非表示
        $('#update-info').text('');
        
        // 初期値設定
        this.setDefaultValues();
        
        // ダイアログ表示
        this.show();
    },
    
    /**
     * 編集モードで開く
     * @param {number} seqno SEQNO
     */
    openEdit: function(seqno) {
        var self = this;
        
        this.mode = 'edit';
        this.currentSeqno = seqno;
        this.formChanged = false;
        
        // フォームリセット
        this.resetForm();
        
        // データ取得
        AppLoading.show();

        AppAjax.post('support_ajax01.php', {
            action: 'get',
            seqno: seqno
        }, function(response) {
            AppLoading.hide();
            
            if (response.status === 'success' && response.data) {
                self.setFormData(response.data);

                // タイトル設定
                $('#dialog-title').text('サポート報告書 編集');

                // ボタン表示制御（編集モードでは削除ボタンを表示）
                self.setDeleteButtonVisibility(true);
                $('#btn-save').text('更新');
                
                // ダイアログ表示
                self.show();
            } else {
                AppMessage.showError(response.message || 'データの取得に失敗しました。');
            }
        }, function(message) {
            AppLoading.hide();
            AppMessage.showError(message);
        });
    },
    
    /**
     * ダイアログを表示
     */
    show: function() {
        var self = this;
        $('#input-dialog-overlay').addClass('active');
        $('body').css('overflow', 'hidden');
        
        // 最初の入力項目にフォーカス
        setTimeout(function() {
            $('#kokyaku-code').focus();
            // 変更フラグをリセット
            setTimeout(function() {
                self.formChanged = false;
            }, 50);
        }, 100);
    },
    
    /**
     * ダイアログを閉じる
     */
    close: function() {
        // 変更確認
        if (this.formChanged) {
            if (!confirm('入力内容が保存されていません。閉じてもよろしいですか？')) {
                return;
            }
        }
        
        // 変更フラグをリセット
        this.formChanged = false;
        
        // ダイアログを閉じる
        $('#input-dialog-overlay').removeClass('active');
        $('body').css('overflow', '');
        this.clearErrors();
    },
    
    /**
     * フォームリセット
     */
    resetForm: function() {
        $('#input-form')[0].reset();
        $('#edit-seqno').val('');
        $('#edit-mode').val('new');
        $('#kokyaku-name-display').text('');
        $('#kokyaku-name').val('');
        this.clearErrors();
        this.selectedKokyaku = null;
    },

    /**
     * 削除ボタンの表示制御
     * @param {boolean} shouldShow 表示する場合はtrue
     */
    setDeleteButtonVisibility: function(shouldShow) {
        var $btn = $('#btn-delete');
        if (shouldShow) {
            // inlineスタイルのdisplay: none;を上書きし確実に表示
            $btn.removeAttr('style');
            $btn.css('display', 'inline-block');
        } else {
            $btn.css('display', 'none');
        }
    },
    
    /**
     * デフォルト値設定（新規登録時）
     */
    setDefaultValues: function() {
        // 対応日：当日
        var today = new Date();
        var dateStr = today.getFullYear() + '-' + 
                      ('0' + (today.getMonth() + 1)).slice(-2) + '-' + 
                      ('0' + today.getDate()).slice(-2);
        $('#taiou-date').val(dateStr);
        
        // 開始時刻：現在時刻を5分単位に丸める
        var minutes = Math.round(today.getMinutes() / 5) * 5;
        if (minutes >= 60) {
            today.setHours(today.getHours() + 1);
            minutes = 0;
        }
        var timeStr = ('0' + today.getHours()).slice(-2) + ':' + ('0' + minutes).slice(-2);
        $('#taiou-time-start').val(timeStr);
        $('#taiou-time-end').val(timeStr);
        
        // 作業担当：ログインユーザー
        if (typeof currentUserId !== 'undefined' && currentUserId) {
            $('#tantou-code').val(currentUserId);
        }
    },
    
    /**
     * フォームにデータを設定（編集時）
     * ※エイリアス名対応（support_ajax01.phpのgetData関数のレスポンス形式に合わせる）
     * @param {object} data データ
     */
    setFormData: function(data) {
        var row = data.data || data;
        
        // SEQNO（エイリアス: seqno）
        $('#edit-seqno').val(row.seqno || row.SEQNO);
        $('#edit-mode').val('edit');
        
        // 顧客情報（エイリアス: kokyaku_code, kokyaku_name, kokyaku_tantou）
        $('#kokyaku-code').val(row.kokyaku_code || row.顧客コード || '');
        $('#kokyaku-name').val(row.kokyaku_name || row.顧客名 || '');
        $('#kokyaku-name-display').text(row.kokyaku_name || row.顧客名 || '');
        $('#kokyaku-tanto').val(row.kokyaku_tantou || row.顧客担当者名 || '');
        
        // 対応日時（エイリアス: taiou_start, taiou_end）
        if (row.taiou_start || row.対応開始日時) {
            var startDt = new Date(row.taiou_start || row.対応開始日時);
            $('#taiou-date').val(this.formatDate(startDt));
            $('#taiou-time-start').val(this.formatTime(startDt));
        }
        if (row.taiou_end || row.対応終了日時) {
            var endDt = new Date(row.taiou_end || row.対応終了日時);
            $('#taiou-time-end').val(this.formatTime(endDt));
        }
        
        // 商品（エイリアス: shohin_code1, shohin_code2, shohin_code3）
        $('#shohin-code1').val(row.shohin_code1 || row.商品コード1 || '');
        $('#shohin-code2').val(row.shohin_code2 || row.商品コード2 || '');
        $('#shohin-code3').val(row.shohin_code3 || row.商品コード3 || '');
        
        // 担当者・区分（エイリアス: tantou_code, kubun_code, hikitsugi_code）
        $('#tantou-code').val(row.tantou_code || row.作業担当コード || '');
        $('#kubun-code').val(row.kubun_code || row.対応区分コード || '');
        $('#hikitsugi-code').val(row.hikitsugi_code || row.引継担当コード || '');
        
        // 対応内容フラグ（エイリアス: flag1～flag20）
        $('input[name="taiou_flag[]"]').prop('checked', false);
        for (var i = 1; i <= 20; i++) {
            var aliasKey = 'flag' + i;
            var japaneseKey = '対応内容フラグ' + i;
            // -1または1をTrueとして扱う（Access互換）
            if (row[aliasKey] == 1 || row[aliasKey] == -1 || row[japaneseKey] == 1 || row[japaneseKey] == -1) {
                $('#taiou-flag-' + i).prop('checked', true);
            }
        }
        
        // 報告内容（エイリアス: houkoku）
        $('#houkoku-naiyo').val(row.houkoku || row.報告内容 || '');
        
        // 更新日時表示（エイリアス: update_datetime）
        if (row.update_datetime || row.更新日時) {
            var updateDt = new Date(row.update_datetime || row.更新日時);
            $('#update-info').text('更新日時: ' + this.formatDateTime(updateDt));
        } else {
            $('#update-info').text('');
        }
        
        // 元データ保存（変更検出用）
        this.originalData = this.collectFormData();
        // 変更フラグはshow()内でリセットされる
    },
    
    /**
     * フォームデータを収集
     * @returns {object} フォームデータ
     */
    collectFormData: function() {
        var data = {
            seqno: $('#edit-seqno').val(),
            mode: $('#edit-mode').val(),
            kokyaku_code: $('#kokyaku-code').val(),
            kokyaku_tanto: $('#kokyaku-tanto').val(),
            taiou_date: $('#taiou-date').val(),
            taiou_time_start: $('#taiou-time-start').val(),
            taiou_time_end: $('#taiou-time-end').val(),
            shohin_code1: $('#shohin-code1').val(),
            shohin_code2: $('#shohin-code2').val(),
            shohin_code3: $('#shohin-code3').val(),
            tantou_code: $('#tantou-code').val(),
            kubun_code: $('#kubun-code').val(),
            hikitsugi_code: $('#hikitsugi-code').val(),
            houkoku_naiyo: $('#houkoku-naiyo').val(),
            taiou_flag: []
        };
        
        // 対応内容フラグ
        $('input[name="taiou_flag[]"]:checked').each(function() {
            data.taiou_flag.push($(this).val());
        });
        
        return data;
    },
    
    /**
     * 保存処理
     */
    save: function() {
        var self = this;
        
        // クライアント側バリデーション
        if (!this.validate()) {
            return;
        }
        
        var formData = this.collectFormData();
        var isUpdate = (this.mode === 'edit');
        formData.action = isUpdate ? 'update' : 'insert';
        
        AppLoading.show();
        
        AppAjax.post('support_ajax02.php', formData, function(response) {
            AppLoading.hide();
            
            if (response.status === 'success') {
                // 成功メッセージ
                AppMessage.showSuccess(response.message || '保存しました。');

                // 更新時はダイアログを閉じる、新規登録時は新規入力状態に戻す
                if (isUpdate) {
                    // 更新後はダイアログを閉じる
                    self.formChanged = false;
                    $('#input-dialog-overlay').removeClass('active');
                    $('body').css('overflow', '');
                    self.clearErrors();
                } else {
                    // 新規登録後はダイアログを開いたまま、新規入力状態に戻す
                    self.resetForNewEntry();
                }

                // 一覧を更新
                if (typeof loadData === 'function') {
                    loadData(currentPage || 1);
                }
            } else {
                // エラー表示
                self.showErrors(response.errors || [response.message]);
            }
        }, function(message) {
            AppLoading.hide();
            self.showErrors([message]);
        });
    },

    /**
     * 保存完了後の再初期化処理（新規入力モードに戻す）
     */
    resetForNewEntry: function() {
        // モードとSEQNOを新規状態に
        this.mode = 'new';
        this.currentSeqno = null;
        $('#edit-mode').val('new');
        $('#edit-seqno').val('');

        // ボタンやタイトルを新規モード表示に戻す
        $('#dialog-title').text('サポート報告書 新規登録');
        this.setDeleteButtonVisibility(false);
        $('#btn-save').text('登録');
        $('#update-info').text('');

        // 入力内容とエラー状態をリセットし、初期値を再設定
        this.resetForm();
        this.setDefaultValues();

        // 新規状態を元データとして保持し、変更フラグを下げる
        this.formChanged = false;
        this.originalData = this.collectFormData();

        // フォーカスを先頭項目へ
        $('#kokyaku-code').focus();
    },
    
    /**
     * 削除確認
     */
    confirmDelete: function() {
        var self = this;
        
        if (!this.currentSeqno) {
            return;
        }
        
        if (!confirm('このサポート報告書を削除してもよろしいですか？\nこの操作は取り消せません。')) {
            return;
        }
        
        AppLoading.show();
        
        AppAjax.post('support_ajax02.php', {
            action: 'delete',
            seqno: this.currentSeqno
        }, function(response) {
            AppLoading.hide();
            
            if (response.status === 'success') {
                AppMessage.showSuccess(response.message || '削除しました。');
                
                // ダイアログを閉じる
                self.formChanged = false;
                $('#input-dialog-overlay').removeClass('active');
                $('body').css('overflow', '');
                
                // 一覧を更新
                if (typeof loadData === 'function') {
                    loadData(currentPage || 1);
                }
            } else {
                self.showErrors([response.message || '削除に失敗しました。']);
            }
        }, function(message) {
            AppLoading.hide();
            self.showErrors([message]);
        });
    },
    
    /**
     * クライアント側バリデーション
     * @returns {boolean} バリデーション結果
     */
    validate: function() {
        var errors = [];
        
        // 必須チェック：顧客コード
        if ($('#kokyaku-code').val().trim() === '') {
            errors.push('顧客コードを入力してください。');
            $('#kokyaku-code').addClass('input-error');
        } else {
            $('#kokyaku-code').removeClass('input-error');
        }
        
        // 顧客存在チェック
        if ($('#kokyaku-code').val().trim() !== '' && $('#kokyaku-name').val() === '') {
            errors.push('有効な顧客コードを入力してください。');
            $('#kokyaku-code').addClass('input-error');
        }
        
        // 必須チェック：対応日
        if ($('#taiou-date').val() === '') {
            errors.push('対応日を入力してください。');
            $('#taiou-date').addClass('input-error');
        } else {
            $('#taiou-date').removeClass('input-error');
        }
        
        // 必須チェック：開始時刻
        if ($('#taiou-time-start').val() === '') {
            errors.push('開始時刻を入力してください。');
            $('#taiou-time-start').addClass('input-error');
        } else {
            $('#taiou-time-start').removeClass('input-error');
        }
        
        // 時刻の前後チェック
        if ($('#taiou-time-start').val() && $('#taiou-time-end').val()) {
            if ($('#taiou-time-start').val() > $('#taiou-time-end').val()) {
                errors.push('終了時刻は開始時刻以降を指定してください。');
                $('#taiou-time-end').addClass('input-error');
            } else {
                $('#taiou-time-end').removeClass('input-error');
            }
        }
        
        // 必須チェック：作業担当
        if ($('#tantou-code').val() === '') {
            errors.push('作業担当を選択してください。');
            $('#tantou-code').addClass('input-error');
        } else {
            $('#tantou-code').removeClass('input-error');
        }
        
        // 必須チェック：報告内容
        if ($('#houkoku-naiyo').val().trim() === '') {
            errors.push('報告内容を入力してください。');
            $('#houkoku-naiyo').addClass('input-error');
        } else {
            $('#houkoku-naiyo').removeClass('input-error');
        }
        
        // 商品重複チェック
        var products = [
            $('#shohin-code1').val(),
            $('#shohin-code2').val(),
            $('#shohin-code3').val()
        ].filter(function(v) { return v !== ''; });
        
        var uniqueProducts = products.filter(function(v, i, arr) {
            return arr.indexOf(v) === i;
        });
        
        if (products.length !== uniqueProducts.length) {
            errors.push('同じ商品が複数選択されています。');
        }
        
        if (errors.length > 0) {
            this.showErrors(errors);
            return false;
        }
        
        this.clearErrors();
        return true;
    },
    
    /**
     * エラー表示
     * @param {array} errors エラーメッセージ配列
     */
    showErrors: function(errors) {
        var html = '';
        for (var i = 0; i < errors.length; i++) {
            html += '<p class="error-message"><span class="icon-error">！</span>' + 
                    AppUtil.escapeHtml(errors[i]) + '</p>';
        }
        $('#dialog-error-box').html(html).show();
        
        // エラー表示位置にスクロール
        $('.modal-body').scrollTop(0);
    },
    
    /**
     * エラークリア
     */
    clearErrors: function() {
        $('#dialog-error-box').empty().hide();
        $('.input-error').removeClass('input-error');
        $('.field-error-message').remove();
    },
    
    /**
     * 顧客名取得（Ajax）
     * @param {string} code 顧客コード
     */
    fetchKokyakuName: function(code) {
        var self = this;
        
        if (!code || code === '') {
            return;
        }
        
        AppAjax.post('support_ajax02.php', {
            action: 'search_customer',
            kokyaku_code: code
        }, function(response) {
            if (response.status === 'success' && response.data) {
                $('#kokyaku-name-display').text(response.data.kokyaku_name || '');
                $('#kokyaku-name').val(response.data.kokyaku_name || '');
                $('#kokyaku-code').removeClass('input-error');
            } else {
                $('#kokyaku-name-display').text('');
                $('#kokyaku-name').val('');
                $('#kokyaku-code').addClass('input-error');
            }
        }, function() {
            $('#kokyaku-name-display').text('');
            $('#kokyaku-name').val('');
        });
    },
    
    /**
     * 商品重複チェック表示
     */
    checkProductDuplicate: function() {
        var products = [
            { id: '#shohin-code1', val: $('#shohin-code1').val() },
            { id: '#shohin-code2', val: $('#shohin-code2').val() },
            { id: '#shohin-code3', val: $('#shohin-code3').val() }
        ];
        
        // 一旦全てのエラー状態をクリア
        products.forEach(function(p) {
            $(p.id).removeClass('input-error');
        });
        
        // 重複チェック
        for (var i = 0; i < products.length; i++) {
            if (products[i].val === '') continue;
            
            for (var j = i + 1; j < products.length; j++) {
                if (products[j].val === '') continue;
                
                if (products[i].val === products[j].val) {
                    $(products[i].id).addClass('input-error');
                    $(products[j].id).addClass('input-error');
                }
            }
        }
    },
    
    /**
     * 顧客選択を設定（検索ダイアログから）
     * @param {string} code 顧客コード
     * @param {string} name 顧客名
     */
    setKokyaku: function(code, name) {
        $('#kokyaku-code').val(code);
        $('#kokyaku-name').val(name);
        $('#kokyaku-name-display').text(name);
        $('#kokyaku-code').removeClass('input-error');
        this.formChanged = true;
    },
    
    /**
     * 定型文を挿入
     * @param {string} text 定型文テキスト
     */
    insertTemplate: function(text) {
        var textarea = document.getElementById('houkoku-naiyo');
        var startPos = textarea.selectionStart;
        var endPos = textarea.selectionEnd;
        var currentText = textarea.value;
        
        // カーソル位置に挿入（選択範囲がある場合は置換）
        textarea.value = currentText.substring(0, startPos) + text + currentText.substring(endPos);
        
        // カーソル位置を調整
        var newPos = startPos + text.length;
        textarea.setSelectionRange(newPos, newPos);
        textarea.focus();
        
        this.formChanged = true;
    },
    
    /**
     * 日付フォーマット（YYYY-MM-DD）
     */
    formatDate: function(date) {
        return date.getFullYear() + '-' + 
               ('0' + (date.getMonth() + 1)).slice(-2) + '-' + 
               ('0' + date.getDate()).slice(-2);
    },
    
    /**
     * 時刻フォーマット（HH:MM）
     */
    formatTime: function(date) {
        return ('0' + date.getHours()).slice(-2) + ':' + 
               ('0' + date.getMinutes()).slice(-2);
    },
    
    /**
     * 日時フォーマット（YYYY/MM/DD HH:MM）
     */
    formatDateTime: function(date) {
        return date.getFullYear() + '/' + 
               ('0' + (date.getMonth() + 1)).slice(-2) + '/' + 
               ('0' + date.getDate()).slice(-2) + ' ' +
               ('0' + date.getHours()).slice(-2) + ':' + 
               ('0' + date.getMinutes()).slice(-2);
    }
};


/**
 * 顧客検索ダイアログ管理オブジェクト
 */
var KokyakuSearchDialog = {
    
    selectedCode: null,
    selectedName: null,
    
    /**
     * 初期化
     */
    init: function() {
        var self = this;
        
        // 閉じるボタン
        $('#btn-kokyaku-search-close, #btn-kokyaku-search-cancel').on('click', function() {
            self.close();
        });
        
        // オーバーレイクリックで閉じる
        $('#kokyaku-search-overlay').on('click', function(e) {
            if ($(e.target).hasClass('modal-overlay')) {
                self.close();
            }
        });
        
        // 検索ボタン
        $('#btn-kokyaku-search-exec').on('click', function() {
            self.search();
        });
        
        // Enterキーで検索
        $('#kokyaku-search-keyword').on('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                self.search();
            }
        });
        
        // 結果クリック
        $(document).on('click', '#kokyaku-search-result .search-result-item', function() {
            $('#kokyaku-search-result .search-result-item').removeClass('selected');
            $(this).addClass('selected');
            self.selectedCode = $(this).data('code');
            self.selectedName = $(this).data('name');
            $('#btn-kokyaku-search-select').prop('disabled', false);
        });
        
        // 結果ダブルクリック
        $(document).on('dblclick', '#kokyaku-search-result .search-result-item', function() {
            self.selectedCode = $(this).data('code');
            self.selectedName = $(this).data('name');
            self.select();
        });
        
        // 選択ボタン
        $('#btn-kokyaku-search-select').on('click', function() {
            self.select();
        });
    },
    
    /**
     * ダイアログを開く
     */
    open: function() {
        this.selectedCode = null;
        this.selectedName = null;
        $('#kokyaku-search-keyword').val('');
        $('#kokyaku-search-result').html('<div class="search-no-result">検索キーワードを入力してください</div>');
        $('#btn-kokyaku-search-select').prop('disabled', true);
        
        $('#kokyaku-search-overlay').addClass('active');
        
        setTimeout(function() {
            $('#kokyaku-search-keyword').focus();
        }, 300);
    },
    
    /**
     * ダイアログを閉じる
     */
    close: function() {
        $('#kokyaku-search-overlay').removeClass('active');
    },
    
    /**
     * 検索実行
     */
    search: function() {
        var self = this;
        var keyword = $('#kokyaku-search-keyword').val().trim();
        
        if (keyword === '') {
            $('#kokyaku-search-result').html('<div class="search-no-result">検索キーワードを入力してください</div>');
            return;
        }
        
        AppLoading.show();
        
        AppAjax.post('support_ajax02.php', {
            action: 'search_customer_list',
            keyword: keyword
        }, function(response) {
            AppLoading.hide();
            
            if (response.status === 'success') {
                self.renderResult(response.data || []);
            } else {
                $('#kokyaku-search-result').html('<div class="search-no-result">検索に失敗しました</div>');
            }
        }, function() {
            AppLoading.hide();
            $('#kokyaku-search-result').html('<div class="search-no-result">検索に失敗しました</div>');
        });
    },
    
    /**
     * 検索結果を描画
     * @param {array} data 顧客リスト
     */
    renderResult: function(data) {
        if (!data || data.length === 0) {
            $('#kokyaku-search-result').html('<div class="search-no-result">該当する顧客が見つかりません</div>');
            return;
        }
        
        var html = '';
        for (var i = 0; i < data.length; i++) {
            html += '<div class="search-result-item" data-code="' + data[i].kokyaku_code + '" data-name="' + AppUtil.escapeHtml(data[i].kokyaku_name) + '">';
            html += '<div class="search-result-code">コード: ' + data[i].kokyaku_code + '</div>';
            html += '<div class="search-result-name">' + AppUtil.escapeHtml(data[i].kokyaku_name) + '</div>';
            html += '</div>';
        }
        
        $('#kokyaku-search-result').html(html);
        $('#btn-kokyaku-search-select').prop('disabled', true);
    },
    
    /**
     * 選択確定
     */
    select: function() {
        if (this.selectedCode && this.selectedName) {
            InputDialog.setKokyaku(this.selectedCode, this.selectedName);
            this.close();
        }
    }
};


/**
 * 定型文選択ダイアログ管理オブジェクト
 */
var TemplateDialog = {
    
    templates: [],
    
    /**
     * 初期化
     */
    init: function() {
        var self = this;
        
        // 閉じるボタン
        $('#btn-template-close, #btn-template-cancel').on('click', function() {
            self.close();
        });
        
        // オーバーレイクリックで閉じる
        $('#template-search-overlay').on('click', function(e) {
            if ($(e.target).hasClass('modal-overlay')) {
                self.close();
            }
        });
        
        // 定型文クリック
        $(document).on('click', '#template-list .template-item', function() {
            var text = $(this).data('text');
            InputDialog.insertTemplate(text);
            self.close();
        });
    },
    
    /**
     * ダイアログを開く
     */
    open: function() {
        var self = this;
        
        // 定型文リスト取得
        AppLoading.show();
        
        AppAjax.post('support_ajax02.php', {
            action: 'get_templates'
        }, function(response) {
            AppLoading.hide();
            
            if (response.status === 'success') {
                self.templates = response.data || [];
                self.renderTemplates();
                $('#template-search-overlay').addClass('active');
            } else {
                AppMessage.showError('定型文の取得に失敗しました。');
            }
        }, function() {
            AppLoading.hide();
            AppMessage.showError('定型文の取得に失敗しました。');
        });
    },
    
    /**
     * ダイアログを閉じる
     */
    close: function() {
        $('#template-search-overlay').removeClass('active');
    },
    
    /**
     * 定型文リストを描画
     */
    renderTemplates: function() {
        if (!this.templates || this.templates.length === 0) {
            $('#template-list').html('<div class="search-no-result">定型文が登録されていません</div>');
            return;
        }
        
        var html = '';
        for (var i = 0; i < this.templates.length; i++) {
            var t = this.templates[i];
            // プレビュー用に80文字で切り詰め
            var preview = t.teikei_text || '';
            if (preview.length > 80) {
                preview = preview.substring(0, 80) + '...';
            }
            
            // タイトルは「定型文 + コード」で表示
            var title = '定型文 ' + (t.teikei_code || (i + 1));
            
            html += '<div class="template-item" data-text="' + AppUtil.escapeHtml(t.teikei_text || '').replace(/"/g, '&quot;') + '">';
            html += '<div class="template-item-title">' + AppUtil.escapeHtml(title) + '</div>';
            html += '<div class="template-item-preview">' + AppUtil.escapeHtml(preview) + '</div>';
            html += '</div>';
        }
        
        $('#template-list').html(html);
    }
};


/**
 * 初期化
 */
$(function() {
    InputDialog.init();
    KokyakuSearchDialog.init();
    TemplateDialog.init();
});
