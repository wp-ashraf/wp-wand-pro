<div class="wrap" id="wpwand-bulk-post-generator">
    <h1>Create Bulk Posts </h1>
    <?php $this->limit_text(); ?>

    <div class="wpwand-pgs-wrap">
        <div class="wpwand-pgs-header">
            <div class="step active" data-id="step-1">
                <h4>Step 1</h4>
                <p>Add info</p>
            </div>
            <div class="step" data-id="step-2">
                <h4>Step 2</h4>
                <p>Review titles</p>
            </div>
            <div class="step" data-id="step-3">
                <h4>Step 3</h4>
                <p>Confirmation</p>
            </div>
        </div>
        <div class="wpwand-pgs-content">
            <!-- step 1 -->
            <div id="step-1" class="step-content active">
                <div class="wpwand-nasted-tabs">
                    <a href="" class="active" data-id="wpwand-pgf-custom-wrap">Custom Headlines</a>
                    <a href="" data-id="wpwand-pgf-ai-wrap">AI Generated Headlines</a>
                </div>

                <div class="wpwand-nasted-item active" id="wpwand-pgf-custom-wrap">

                    <form action="" method="post" class=" wpwand-pg-form" id="wpwand-pgf-custom">

                        <div class="wpwand-pgf-row">
                            <div class="wpwand-pgf-label">
                                <label for="topic">Add Your Own Headlines</label>
                                <p>Enter each headline in a single line. You can add as many as you want.</p>
                            </div>
                            <div class="wpwand-pgf-field">
                                <textarea name="titles" id="titles" cols="30" rows="10" placeholder="Your first headline goes here.
Your second headline goes here.
Your third headline goes here." required></textarea>
                            </div>
                        </div>

                        <div class="epwand-pgf-row wpwand-pgf-submit-wrap">
                            <?php wp_nonce_field('post_generate_nonce_action', 'post_generate_nonce'); ?>
                            <button type="submit">Next</button>
                        </div>
                    </form>
                </div>
                <div class="wpwand-nasted-item " id="wpwand-pgf-ai-wrap">

                    <form action="" method="post" class="wpwand-pg-form" id="wpwand-pgf-ai">

                        <div class="wpwand-pgf-row">
                            <div class="wpwand-pgf-label">
                                <label for="topic">Topic</label>
                                <p>Add a topic of your bulk post</p>
                            </div>
                            <div class="wpwand-pgf-field">
                                <input type="text" id="topic" name="topic" placeholder="Digital Marketing" required />
                            </div>
                        </div>
                        <div class="wpwand-pgf-row">
                            <div class="wpwand-pgf-label">
                                <label for="post_count">Number of Posts</label>
                                <p>How many posts do you want to generate at once, maximum 20 posts at a time. </p>
                            </div>
                            <div class="wpwand-pgf-field">
                                <input type="number" id="post_count" max="20" name="post_count"
                                    placeholder="Post to generate" required />
                            </div>
                        </div>
                        <div class="epwand-pgf-row wpwand-pgf-submit-wrap">
                            <?php wp_nonce_field('post_generate_nonce_action', 'post_generate_nonce'); ?>
                            <button type="submit">Next</button>
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
                                <strong>Topic:</strong>
                                <span class="wpwand-pg-info-topic"></span>
                            </div>
                            <div class="wpwand-pg-info ">
                                <strong>Number of Posts:</strong>
                                <span class="wpwand-pg-info-count"></span>
                            </div>
                        </div>
                        <div class="wpwand-pcgf-headings-field-wrap">

                            <div class="wpwand-pg-titles-wrap">
                                <div class="wpwand-pg-titles-header">
                                    <h4> <span class="wpwand-pg-info-count"> </span> Titles Generated </h4>

                                </div>
                                <div class="wpwand-pcgf-titles-wrap">
                                    <div class="wpwand-pcgf-title-list">
                                    </div>

                                </div>
                            </div>
                            <div class="wpwand-pcgf-heading-settings">

                                <div class="wpwand-pgf-field">
                                    <label for="keyword">Keywords to Include <span
                                            class="wpwand-optional-label">(Optional)</span></label>
                                    <textarea id="keyword" name="keyword"
                                        placeholder="Write keyword and separate using comma"></textarea>
                                </div>
                                <div class="wpwand-pgf-field">
                                    <label for="tone">Writing Tone <span
                                            class="wpwand-optional-label">(Optional)</span></label>
                                    <select name="tone" id="tone">
                                        <option value="friendly"> Friendly</option>
                                        <option value="helpful"> Helpful</option>
                                        <option value="informative"> Informative</option>
                                        <option value="aggressive"> Aggressive</option>
                                        <option value="professional"> Professional</option>
                                        <option value="Formal"> Formal</option>
                                        <option value="Informal"> Informal</option>
                                        <option value="Conversational"> Conversational</option>
                                        <option value="Persuasive"> Persuasive</option>
                                        <option value="Witty"> Witty</option>
                                        <option value="Descriptive"> Descriptive</option>
                                        <option value="Expository"> Expository</option>
                                        <option value="Humorous"> Humorous</option>
                                        <option value="Inspirational"> Inspirational</option>
                                        <option value="Funny"> Funny</option>
                                        <option value="Poetic"> Poetic</option>
                                        <option value="Technical"> Technical</option>
                                        <option value="Argumentative"> Argumentative</option>
                                        <option value="Instructional"> Instructional</option>
                                        <option value="Sarcastic"> Sarcastic</option>
                                        <option value="Urgent"> Urgent</option>
                                        <option value="Optimistic"> Optimistic</option>
                                    </select>
                                </div>
                                <div class="wpwand-pgf-field">
                                    <input type="checkbox" id="toc_include" name="toc_include" value="yes" checked>
                                    <label for="toc_include">Include Table of Content (TOC)</label>
                                </div>
                                <div class="wpwand-pgf-field">
                                    <input type="checkbox" id="faq_include" name="faq_include" value="yes" checked>
                                    <label for="faq_include">Include FAQ section at bottom</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="" data-target="step-1" class="wpwand-pgs-back-button">Back</a>
                    <button type="button">Next</button>
                </form>
            </div>

            <!-- Step 3 -->
            <div id="step-3" class="step-content">
                <div class="wpwand-pg-info-wrap">
                    <div class="wpwand-pg-info ">
                        <strong>Topic:</strong>
                        <span class="wpwand-pg-info-topic"></span>
                    </div>
                    <div class="wpwand-pg-info ">
                        <strong>Number of Titles Selected:</strong>
                        <span class="wpwand-pg-info-total-selected"></span>
                    </div>
                </div>
                <p>On your confirmation, your post generation will start in the background.</p>
                <a href="" data-target="step-2" class="wpwand-pgs-back-button">Back</a>
                <button type="button" class="start-generation">Start Generating Posts</button>
            </div>
        </div>
    </div>
</div>


