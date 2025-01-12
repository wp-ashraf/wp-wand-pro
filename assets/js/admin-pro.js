(function ($) {

    $(document).ready(function ($) {

        
        // add template
        $('a.wpwand-btn.wpwand-prompt-add').on('click', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Add Prompt Template',
                html: `
                <label class="swal2-input-label" for="wpwand--addprmt-title">` + wpwand_pro_glb.addprmt_title_label + `</label>
                <input name="wpwand--addprmt-title" id="wpwand--addprmt-title" class="swal2-input" placeholder="` + wpwand_pro_glb.addprmt_title_placeholder + `" required>
                <label class="swal2-input-label" for="wpwand--addprmt-prompt">` + wpwand_pro_glb.addprmt_prompt_label + `</label>
                <textarea name="wpwand--addprmt-prompt" id="wpwand--addprmt-prompt" class="swal2-input" placeholder="` + wpwand_pro_glb.addprmt_prompt_placeholder + `" required></textarea>
                <p>` + wpwand_pro_glb.addprmt_prompt_info + ` </p>
                `,
                showCloseButton: true,
                closeButtonHtml: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 18L18 6M6 6L18 18" stroke="#7C838A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>`,
                showCancelButton: false,
                confirmButtonText: 'Add Prompt',
                // cancelButtonText: UltimateDoc.cancelBtn,
                customClass: 'wpwand_form_modal',
                preConfirm: (value) => {
                    // You can add your custom validation logic here
                    var title = document.getElementById('wpwand--addprmt-title').value,
                        prompt = document.getElementById('wpwand--addprmt-prompt').value

                    if (!title || !prompt) {
                        Swal.showValidationMessage('Both fields are required');
                        return false;
                    }
                    // You can add more validation rules as needed
                }
            }).then(function (input) {
                if (input.isDismissed) {
                    return;
                }

                console.log(input);
                if (input.value === false || input.value === '') {
                    return false;
                }
                var title = document.getElementById('wpwand--addprmt-title').value,
                    prompt = document.getElementById('wpwand--addprmt-prompt').value
                $.post({
                    url: wpwand_glb.ajax_url,
                    data: {
                        action: 'wpwand_add_prompt',
                        nonce: wpwand_glb.nonce,
                        title,
                        prompt,
                        type: 'template'
                    },
                    success: function (res) {

                        if (false != res) {
                            $('#wpwand-ntd-tab-templates table tbody').append(`
                            <tr data-id="${res}">
                                    <td>
                                    <a href="" class="wpwand-promtmpt-edit">${title} </a>
                                    <div class="hidden wpwand-data-prompt">
                                    ${prompt}
                                </div>
                                    </td>

                                    <td class="wpwand-table-action"><a href="" class="wpwand-table-btn wpwand-promtmpt-edit">Edit</a><a href="" class="wpwand-table-btn delete">Remove</a></td>
                                </tr>
                            `);
                            Swal.fire({
                                title: 'Successfully Added!',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: {
                                    popup: 'wpwand-swal-alert wpwand-swal-alert-approve-pgc',
                                    // icon: 'wpwand-swal-alert',
                                }
                            }).then(function () {
                                window.location.reload();
                            })
                        }
                    },
                    error: function (error) {
                        alert(error);
                    },
                });

            });
        })

        // edit template
        $('a.wpwand-promtmpt-edit').on('click', function (e) {
            e.preventDefault();
            const $parent = $(this).closest('tr')
            var title = $parent.find('.wpwand-table-name a').text().trim(),
                prompt = $parent.find('.wpwand-table-name .wpwand-data-prompt').text().trim(),
                id = $(this).closest('tr').data('id');

            console.log(title)

            Swal.fire({
                title: 'Edit Prompt Template',
                html: `
                <label class="swal2-input-label" for="wpwand--updtprmt-title-${id}">${wpwand_pro_glb.addprmt_title_label}</label>
                <input name="wpwand--updtprmt-title-${id}" id="wpwand--updtprmt-title-${id}" class="swal2-input" value="${title}" htmlEntities="true">
                <label class="swal2-input-label" for="wpwand--updtprmt-prompt-${id}">${wpwand_pro_glb.addprmt_prompt_label}</label>
                <textarea name="wpwand--updtprmt-prompt-${id}" id="wpwand--updtprmt-prompt-${id}" class="swal2-input" >${prompt}</textarea>
                <p>${wpwand_pro_glb.addprmt_prompt_info} </p>
                `,
                closeButtonHtml: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 18L18 6M6 6L18 18" stroke="#7C838A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>`,
                showCloseButton: true,
                showCancelButton: false,
                confirmButtonText: 'Update Prompt',
                // cancelButtonText: UltimateDoc.cancelBtn,
                customClass: 'wpwand_form_modal',
                preConfirm: (value) => {
                    // You can add your custom validation logic here
                    var title = document.getElementById(`wpwand--updtprmt-title-` + id + ``).value,
                        prompt = document.getElementById(`wpwand--updtprmt-prompt-` + id + ``).value

                    if (!title || !prompt) {
                        Swal.showValidationMessage('Both fields are required');
                        return false;
                    }
                    // You can add more validation rules as needed
                }
            }).then(function (input) {
                if (input.isDismissed) {
                    return;
                }

                if (input.value === false || input.value === '') {
                    return false;
                }
                var title = document.getElementById(`wpwand--updtprmt-title-` + id + ``).value,
                    prompt = document.getElementById(`wpwand--updtprmt-prompt-` + id + ``).value
                $.post({
                    url: wpwand_glb.ajax_url,
                    data: {
                        action: 'wpwand_update_prompt',
                        nonce: wpwand_glb.nonce,
                        type: 'template',
                        title,
                        prompt,
                        id
                    },
                    success: function (res) {
                        // var articles = jQuery(parentEvent.target).parents('.ultd--row-actions')
                        //     .siblings('a').find('.ultd--title')
                        // articles.text(updatedTitle);

                        console.log(res)
                        if (false != res) {
                            $parent.find('.wpwand-table-name a').text(title);
                            $parent.find('.wpwand-table-name .wpwand-data-prompt').text(prompt);
                        }

                        // var success_icon = '<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 13L11.6667 15.6667L17 10.3333M25 13C25 19.6274 19.6274 25 13 25C6.37258 25 1 19.6274 1 13C1 6.37258 6.37258 1 13 1C19.6274 1 25 6.37258 25 13Z" stroke="#22C55E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

                        Swal.fire({
                            title: 'Successfully Updated!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500,
                            customClass: {
                                popup: 'wpwand-swal-alert wpwand-swal-alert-approve-pgc',
                                // icon: 'wpwand-swal-alert',
                            }
                        }).then(function () {
                            window.location.reload();
                        })
                    },
                    error: function (error) {
                        alert(error);
                    },
                });

            });
        })

        // add character
        $('a.wpwand-btn.wpwand-prompt-aichar-add').on('click', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Add Ai Character',
                html: `
                <label class="swal2-input-label" for="wpwand--addaichar-title">` + wpwand_pro_glb.addaichar_title_label + `</label>
                <input name="wpwand--addaichar-title" id="wpwand--addaichar-title" class="swal2-input" placeholder="` + wpwand_pro_glb.addaichar_title_placeholder + `" required>
                <label class="swal2-input-label" for="wpwand--addaichar-prompt">` + wpwand_pro_glb.addaichar_prompt_label + `</label>
                <textarea name="wpwand--addaichar-prompt" id="wpwand--addaichar-prompt" class="swal2-input" placeholder="` + wpwand_pro_glb.addaichar_prompt_placeholder + `" required></textarea>
                `,
                showCloseButton: true,
                closeButtonHtml: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 18L18 6M6 6L18 18" stroke="#7C838A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>`,
                showCancelButton: false,
                confirmButtonText: 'Add Character',
                // cancelButtonText: UltimateDoc.cancelBtn,
                customClass: 'wpwand_form_modal',
                preConfirm: (value) => {
                    // You can add your custom validation logic here
                    var title = document.getElementById('wpwand--addaichar-title').value,
                        prompt = document.getElementById('wpwand--addaichar-prompt').value

                    if (!title || !prompt) {
                        Swal.showValidationMessage('Both fields are required');
                        return false;
                    }
                    // You can add more validation rules as needed
                }
            }).then(function (input) {
                if (input.isDismissed) {
                    return;
                }

                console.log(input);
                if (input.value === false || input.value === '') {
                    return false;
                }
                var title = document.getElementById('wpwand--addaichar-title').value,
                    prompt = document.getElementById('wpwand--addaichar-prompt').value
                $.post({
                    url: wpwand_glb.ajax_url,
                    data: {
                        action: 'wpwand_add_prompt',
                        title,
                        prompt,
                        type: 'aichar',
                        nonce: wpwand_glb.nonce

                    },
                    success: function (res) {

                        console.log(res);
                        if (false != res) {
                            $('#wpwand-ntd-tab-aichar table tbody').append(`
                            <tr data-id="${res}">
                                    <td>
                                    <a class="wpwand-promtaichar-edit" href="">${title} </a>
                                    <div class="hidden wpwand-data-prompt">
                                    ${prompt}
                                </div>
                                    </td>
                                    <td class="wpwand-table-action"><a href="" class="wpwand-table-btn wpwand-promtaichar-edit ">Edit</a><a href="" class="wpwand-table-btn delete">Remove</a></td>
                                </tr>
                            `);

                            Swal.fire({
                                title: 'Successfully Added!',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: {
                                    popup: 'wpwand-swal-alert wpwand-swal-alert-approve-pgc',
                                    // icon: 'wpwand-swal-alert',
                                }
                            }).then(function () {
                                window.location.reload();
                            })
                        }
                    },
                    error: function (error) {
                        alert(error);
                    },
                });

            });
        })

        // edit template
        $('a.wpwand-promtaichar-edit').on('click', function (e) {
            e.preventDefault();
            const $parent = $(this).closest('tr')
            var title = $parent.find('.wpwand-table-name a').text().trim(),
                prompt = $parent.find('.wpwand-table-name .wpwand-data-prompt').text().trim(),
                id = $(this).closest('tr').data('id');

            console.log(title)

            Swal.fire({
                title: 'Edit Character',
                html: `
                <label class="swal2-input-label" for="wpwand--updtprmt-title-${id}">${wpwand_pro_glb.addaichar_title_label}</label>
                <input name="wpwand--updtprmt-title-${id}" id="wpwand--updtprmt-title-${id}" class="swal2-input" value="${title}" htmlEntities="true">
                <label class="swal2-input-label" for="wpwand--updtprmt-prompt-${id}">${wpwand_pro_glb.addaichar_prompt_lable}</label>
                <textarea name="wpwand--updtprmt-prompt-${id}" id="wpwand--updtprmt-prompt-${id}" class="swal2-input" >${prompt}</textarea>
                `,
                closeButtonHtml: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 18L18 6M6 6L18 18" stroke="#7C838A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>`,
                showCloseButton: true,
                showCancelButton: false,
                confirmButtonText: 'Update Prompt',
                // cancelButtonText: UltimateDoc.cancelBtn,
                customClass: 'wpwand_form_modal',
                preConfirm: (value) => {
                    // You can add your custom validation logic here
                    var title = document.getElementById(`wpwand--updtprmt-title-` + id + ``).value,
                        prompt = document.getElementById(`wpwand--updtprmt-prompt-` + id + ``).value

                    if (!title || !prompt) {
                        Swal.showValidationMessage('Both fields are required');
                        return false;
                    }
                    // You can add more validation rules as needed
                }
            }).then(function (input) {
                if (input.isDismissed) {
                    return;
                }

                if (input.value === false || input.value === '') {
                    return false;
                }
                var title = document.getElementById(`wpwand--updtprmt-title-` + id + ``).value,
                    prompt = document.getElementById(`wpwand--updtprmt-prompt-` + id + ``).value
                $.post({
                    url: wpwand_glb.ajax_url,
                    data: {
                        action: 'wpwand_update_prompt',
                        nonce: wpwand_glb.nonce,
                        type: 'aichar',
                        title,
                        prompt,
                        id
                    },
                    success: function (res) {
                        // var articles = jQuery(parentEvent.target).parents('.ultd--row-actions')
                        //     .siblings('a').find('.ultd--title')
                        // articles.text(updatedTitle);

                        console.log(res)
                        if (false != res) {
                            $parent.find('.wpwand-table-name a').text(title);
                            $parent.find('.wpwand-table-name .wpwand-data-prompt').text(prompt);
                        }

                        // var success_icon = '<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 13L11.6667 15.6667L17 10.3333M25 13C25 19.6274 19.6274 25 13 25C6.37258 25 1 19.6274 1 13C1 6.37258 6.37258 1 13 1C19.6274 1 25 6.37258 25 13Z" stroke="#22C55E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

                        Swal.fire({
                            title: 'Successfully Updated!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500,
                            customClass: {
                                popup: 'wpwand-swal-alert wpwand-swal-alert-approve-pgc',
                                // icon: 'wpwand-swal-alert',
                            }
                        }).then(function () {
                            window.location.reload();
                        })
                    },
                    error: function (error) {
                        alert(error);
                    },
                });

            });
        })



        $('a.wpwand-promtmpt-delete').on('click', function (e) {
            e.preventDefault();
            const $parent = $(this).closest('tr')
            const id = $parent.data('id');

            Swal.fire({
                title: 'Are you sure you want to delete this item?',
                showDenyButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: `No`,
                customClass: {
                    popup: 'wpwand-swal-alert wpwand-swal-alert-approve-pgc',
                }
            }).then((result) => {
                if (result.isConfirmed) {

                    $.post({
                        url: wpwand_glb.ajax_url,
                        data: {
                            action: 'wpwand_delete_prompt',
                            nonce: wpwand_glb.nonce,
                            id
                        },
                        success: function (res) {
                            Swal.fire({
                                title: 'Successfully Deleted!',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: {
                                    popup: 'wpwand-swal-alert wpwand-swal-alert-approve-pgc',
                                    // icon: 'wpwand-swal-alert',
                                }
                            }).then(function () {
                                $parent.remove();
                                window.location.reload();
                            })
                        },
                        error: function (error) {
                            alert(error);
                        },
                    });

                }
            });

        })

        // Handle bulk action checkboxes
        $('#wpwand-select-all').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('input[name="wpwand-select-post[]"]').prop('checked', isChecked);
        });

        // Variables to track shift selection
        let lastChecked = null;

        // Handle checkbox selection with shift key
        $('input[name="wpwand-select-post[]"]').on('click', function(e) {
            if (!lastChecked) {
                lastChecked = this;
                return;
            }

            if (e.shiftKey) {
                const start = $('input[name="wpwand-select-post[]"]').index(this);
                const end = $('input[name="wpwand-select-post[]"]').index(lastChecked);
                
                $('input[name="wpwand-select-post[]"]')
                    .slice(Math.min(start, end), Math.max(start, end) + 1)
                    .prop('checked', lastChecked.checked);
            }

            lastChecked = this;
        });

        // Update "select all" checkbox when individual checkboxes change
        $('input[name="wpwand-select-post[]"]').on('change', function() {
            const totalCheckboxes = $('input[name="wpwand-select-post[]"]').length;
            const checkedCheckboxes = $('input[name="wpwand-select-post[]"]:checked').length;
            $('#wpwand-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        // Validate bulk action form submission
        $('.wpwand-history-bulk-action input[type="submit"]').on('click', function(e) {
            const $form = $(this).closest('form');
            const $selectedAction = $(this).prev('select').val();
            const $checkedItems = $form.find('input[name="wpwand-select-post[]"]:checked');

            if ($selectedAction === '-1') {
                e.preventDefault();
                alert('Please select an action');
                return false;
            }

            if ($checkedItems.length === 0) {
                e.preventDefault();
                alert('Please select at least one item');
                return false;
            }

            if ($selectedAction === 'delete') {
                if (!confirm('Are you sure you want to delete the selected items?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });

    });

}(jQuery))