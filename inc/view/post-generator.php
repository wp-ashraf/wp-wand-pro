<div class="wrap" id="wpwand-bulk-post-generator">
    <h1><?php _e('Create Bulk Posts', 'wp-wand-pro'); ?></h1>
    <?php $this->limit_text(); ?>

    <div class="wpwand-pgs-wrap">
        <div class="wpwand-pgs-header">
            <div class="step active" data-id="step-1">
                <h4><?php _e('Step 1', 'wp-wand-pro'); ?></h4>
                <p><?php _e('Add info', 'wp-wand-pro'); ?></p>
            </div>
            <div class="step" data-id="step-2">
                <h4><?php _e('Step 2', 'wp-wand-pro'); ?></h4>
                <p><?php _e('Review titles', 'wp-wand-pro'); ?></p>
            </div>
            <div class="step" data-id="step-3">
                <h4><?php _e('Step 3', 'wp-wand-pro'); ?></h4>
                <p><?php _e('Confirmation', 'wp-wand-pro'); ?></p>
            </div>
        </div>
        <div class="wpwand-pgs-content">
            <!-- step 1 -->
            <div id="step-1" class="step-content active">
                <div class="wpwand-nasted-tabs">
                    <a href="" class="active" data-id="wpwand-pgf-custom-wrap"><?php _e('Custom Headlines', 'wp-wand-pro'); ?></a>
                    <a href="" data-id="wpwand-pgf-ai-wrap"><?php _e('AI Generated Headlines', 'wp-wand-pro'); ?></a>
                </div>

                <div class="wpwand-nasted-item active" id="wpwand-pgf-custom-wrap">

                    <form action="" method="post" class=" wpwand-pg-form" id="wpwand-pgf-custom">

                        <div class="wpwand-pgf-row">
                            <div class="wpwand-pgf-label">
                                <label for="topic"><?php _e('Add Your Own Headlines', 'wp-wand-pro'); ?></label>
                                <p><?php _e('Enter each headline in a single line. You can add as many as you want.', 'wp-wand-pro'); ?></p>
                            </div>
                            <div class="wpwand-pgf-field">
                                <textarea name="titles" id="titles" cols="30" rows="10" placeholder="<?php _e('Your first headline goes here.
Your second headline goes here.
Your third headline goes here.', 'wp-wand-pro'); ?>" required></textarea>
                            </div>
                        </div>

                        <div class="epwand-pgf-row wpwand-pgf-submit-wrap">
                            <?php wp_nonce_field('post_generate_nonce_action', 'post_generate_nonce'); ?>
                            <button type="submit"><?php _e('Next', 'wp-wand-pro'); ?></button>
                        </div>
                    </form>
                </div>
                <div class="wpwand-nasted-item " id="wpwand-pgf-ai-wrap">

                    <form action="" method="post" class="wpwand-pg-form" id="wpwand-pgf-ai">

                        <div class="wpwand-pgf-row">
                            <div class="wpwand-pgf-label">
                                <label for="topic"><?php _e('Topic', 'wp-wand-pro'); ?></label>
                                <p><?php _e('Add a topic of your bulk post', 'wp-wand-pro'); ?></p>
                            </div>
                            <div class="wpwand-pgf-field">
                                <input type="text" id="topic" name="topic" placeholder="<?php _e('Digital Marketing', 'wp-wand-pro'); ?>" required />
                            </div>
                        </div>
                        <div class="wpwand-pgf-row">
                            <div class="wpwand-pgf-label">
                                <label for="post_count"><?php _e('Number of Posts', 'wp-wand-pro'); ?></label>
                                <p><?php _e('How many posts do you want to generate at once, maximum 20 posts at a time. ', 'wp-wand-pro'); ?></p>
                            </div>
                            <div class="wpwand-pgf-field">
                                <input type="number" id="post_count" max="20" name="post_count"
                                    placeholder="<?php _e('Post to generate', 'wp-wand-pro'); ?>" required />
                            </div>
                        </div>
                        <div class="epwand-pgf-row wpwand-pgf-submit-wrap">
                            <?php wp_nonce_field('post_generate_nonce_action', 'post_generate_nonce'); ?>
                            <button type="submit"><?php _e('Next', 'wp-wand-pro'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Step 2 -->
            <div id="step-2" class="step-content">
                <form action="" method="post" id="wpwand-post-content-generate-form">
                    <div class="wpwand-pcgf-titles-wrap">
                        <div class="wpwand-pg-info-wrap">
                            <div class="wpwand-pg-info ">
                                <strong><?php _e('Topic:', 'wp-wand-pro'); ?></strong>
                                <span class="wpwand-pg-info-topic"></span>
                            </div>
                            <div class="wpwand-pg-info ">
                                <strong><?php _e('Number of Posts:', 'wp-wand-pro'); ?></strong>
                                <span class="wpwand-pg-info-count"></span>
                            </div>
                        </div>
                        <div class="wpwand-pcgf-headings-field-wrap">

                            <div class="wpwand-pg-titles-wrap">
                                <div class="wpwand-pg-titles-header">
                                    <h4> <span class="wpwand-pg-info-count"> </span> <?php _e('Titles Generated', 'wp-wand-pro'); ?> </h4>

                                </div>
                                <div class="wpwand-pcgf-titles-wrap">
                                    <div class="wpwand-pcgf-title-list">
                                    </div>

                                </div>
                            </div>
                            <div class="wpwand-pcgf-heading-settings">

                                <div class="wpwand-pgf-field">
                                    <label for="keyword"><?php _e('Keywords to Include', 'wp-wand-pro'); ?> <span
                                            class="wpwand-optional-label">(<?php _e('Optional', 'wp-wand-pro'); ?>)</span></label>
                                    <textarea id="keyword" name="keyword"
                                        placeholder="<?php _e('Write keyword and separate using comma', 'wp-wand-pro'); ?>"></textarea>
                                </div>
                                <div class="wpwand-pgf-field">
                                    <label for="tone"><?php _e('Writing Tone', 'wp-wand-pro'); ?> <span
                                            class="wpwand-optional-label">(<?php _e('Optional', 'wp-wand-pro'); ?>)</span></label>
                                    <select name="tone" id="tone">
                                        <option value="friendly"> <?php _e('Friendly', 'wp-wand-pro'); ?></option>
                                        <option value="helpful"> <?php _e('Helpful', 'wp-wand-pro'); ?></option>
                                        <option value="informative"> <?php _e('Informative', 'wp-wand-pro'); ?></option>
                                        <option value="aggressive"> <?php _e('Aggressive', 'wp-wand-pro'); ?></option>
                                        <option value="professional"> <?php _e('Professional', 'wp-wand-pro'); ?></option>
                                        <option value="Formal"> <?php _e('Formal', 'wp-wand-pro'); ?></option>
                                        <option value="Informal"> <?php _e('Informal', 'wp-wand-pro'); ?></option>
                                        <option value="Conversational"> <?php _e('Conversational', 'wp-wand-pro'); ?></option>
                                        <option value="Persuasive"> <?php _e('Persuasive', 'wp-wand-pro'); ?></option>
                                        <option value="Witty"> <?php _e('Witty', 'wp-wand-pro'); ?></option>
                                        <option value="Descriptive"> <?php _e('Descriptive', 'wp-wand-pro'); ?></option>
                                        <option value="Expository"> <?php _e('Expository', 'wp-wand-pro'); ?></option>
                                        <option value="Humorous"> <?php _e('Humorous', 'wp-wand-pro'); ?></option>
                                        <option value="Inspirational"> <?php _e('Inspirational', 'wp-wand-pro'); ?></option>
                                        <option value="Funny"> <?php _e('Funny', 'wp-wand-pro'); ?></option>
                                        <option value="Poetic"> <?php _e('Poetic', 'wp-wand-pro'); ?></option>
                                        <option value="Technical"> <?php _e('Technical', 'wp-wand-pro'); ?></option>
                                        <option value="Argumentative"> <?php _e('Argumentative', 'wp-wand-pro'); ?></option>
                                        <option value="Instructional"> <?php _e('Instructional', 'wp-wand-pro'); ?></option>
                                        <option value="Sarcastic"> <?php _e('Sarcastic', 'wp-wand-pro'); ?></option>
                                        <option value="Urgent"> <?php _e('Urgent', 'wp-wand-pro'); ?></option>
                                        <option value="Optimistic"> <?php _e('Optimistic', 'wp-wand-pro'); ?></option>
                                    </select>
                                </div>
                                <div class="wpwand-pgf-field">
                                    <input type="checkbox" id="toc_include" name="toc_include" value="yes" checked>
                                    <label for="toc_include"><?php _e('Include Table of Content (TOC)', 'wp-wand-pro'); ?></label>
                                </div>
                                <div class="wpwand-pgf-field">
                                    <input type="checkbox" id="faq_include" name="faq_include" value="yes" checked>
                                    <label for="faq_include"><?php _e('Include FAQ section at bottom', 'wp-wand-pro'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="" data-target="step-1" class="wpwand-pgs-back-button"><?php _e('Back', 'wp-wand-pro'); ?></a>
                    <button type="button"><?php _e('Next', 'wp-wand-pro'); ?></button>
                </form>
            </div>

            <!-- Step 3 -->
            <div id="step-3" class="step-content">
                <div class="wpwand-pg-info-wrap">
                    <div class="wpwand-pg-info ">
                        <strong><?php _e('Topic:', 'wp-wand-pro'); ?></strong>
                        <span class="wpwand-pg-info-topic"></span>
                    </div>
                    <div class="wpwand-pg-info ">
                        <strong><?php _e('Number of Titles Selected:', 'wp-wand-pro'); ?></strong>
                        <span class="wpwand-pg-info-total-selected"></span>
                    </div>
                </div>
                <p><?php _e('On your confirmation, your post generation will start in the background.', 'wp-wand-pro'); ?></p>
                <a href="" data-target="step-2" class="wpwand-pgs-back-button"><?php _e('Back', 'wp-wand-pro'); ?></a>
                <button type="button" class="start-generation"><?php _e('Start Generating Posts', 'wp-wand-pro'); ?></button>
            </div>
        </div>
    </div>
</div>


