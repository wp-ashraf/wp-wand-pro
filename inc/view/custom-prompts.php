<div id="custom-pro" class="tab-panel" style="display:none;">
    <div class="wpwand-nasted-tabs">
        <a href="" class="active" data-id="wpwand-ntd-tab-templates">Custom Prompt Template</a>
        <a href="" data-id="wpwand-ntd-tab-aichar">AI Character</a>
    </div>

    <div class="wpwand-nasted-item active" id="wpwand-ntd-tab-templates">
        <div class="wpwand-nasted-header-wrap">

            <div class="wpwand-tab-header">
                <h4>
                    <?php esc_html_e('Custom Prompt Templates', 'wp-wand-pro'); ?>
                </h4>
                <p class="\">Create your own AI Character template and use it generate quality content. If you don’t see it in the template list, make sure to refresh the page.</p>

            </div>
            <a href="" class="wpwand-btn wpwand-prompt-add">Add New Template</a>
        </div>


        <div class="wpwand-table-wrap">
            <?php if ($data): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="manage-column" width="40%">Template Name</th>
                            <th class="manage-column"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($data as $row): ?>
                            <?php
                            if ('template' == $row['type']):
                                ?>
                                <tr data-id="<?php echo $row['id'] ?>">
                                    <td class="wpwand-table-name">
                                        <a href="<?php echo admin_url('admin.php?page=wpwand-post-generator&view=' . $row['id']) ?>"
                                            class="wpwand-promtmpt-edit"><?php echo htmlspecialchars(wp_trim_words($row['title'], 10, '...')) ?> </a>
                                        <div class="hidden wpwand-data-prompt">
                                            <?php echo esc_html($row['prompt']) ?>
                                        </div>
                                    </td>

                                    <td class="wpwand-table-action">
                                        <a href="" class="wpwand-table-btn   ">
                                            <?php echo esc_html__('Edit', 'wp-wand-pro') ?>
                                        </a>
                                        <a href="" class="wpwand-table-btn delete wpwand-promtmpt-delete">
                                            <?php echo esc_html__('Remove', 'wp-wand-pro') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>
                    <?php echo esc_html__('No custom template available', 'wp-wand-pro') ?>
                </p>
            <?php endif; ?>
        </div>

    </div>

    <div class="wpwand-nasted-item" id="wpwand-ntd-tab-aichar">
        <div class="wpwand-nasted-header-wrap">

            <div class="wpwand-tab-header">
                <h4>
                    <?php esc_html_e('Custom AI Characters', 'wp-wand-pro'); ?>
                </h4>
                <p class="\">Create your own prompt template and use it generate quality content. If you don’t see it in
                    the template list, make sure to refresh the page,</p>

            </div>
            <a href="" class="wpwand-btn wpwand-prompt-aichar-add">Add New Template</a>
        </div>


        <div class="wpwand-table-wrap">
            <?php if ($character): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="manage-column" width="40%">Character Name</th>
                            <th class="manage-column"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($character as $row): ?>
                            <?php
                            if ('aichar' == $row['type']):
                                ?>
                                <tr data-id="<?php echo $row['id'] ?>">
                                    <td class="wpwand-table-name">
                                        <a href="<?php echo admin_url('admin.php?page=wpwand-post-generator&view=' . $row['id']) ?>"
                                            class="wpwand-promtaichar-edit"><?php echo htmlspecialchars(wp_trim_words($row['title'], 10, '...')) ?> </a>
                                        <div class="hidden wpwand-data-prompt">
                                            <?php echo esc_html($row['prompt']) ?>
                                        </div>
                                    </td>

                                    <td class="wpwand-table-action">
                                        <a href="" class="wpwand-table-btn wpwand-promtaichar-edit ">
                                            <?php echo esc_html__('Edit', 'wp-wand-pro') ?>
                                        </a>
                                        <a href="" class="wpwand-table-btn delete wpwand-promtmpt-delete">
                                            <?php echo esc_html__('Remove', 'wp-wand-pro') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>
                    <?php echo esc_html__('No ai character available', 'wp-wand-pro') ?>
                </p>
            <?php endif; ?>
        </div>

    </div>



    <?php // wpwand_pro_card()?>
</div>