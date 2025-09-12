<?php

if (!defined('ABSPATH')) {
    exit('You are not allowed');
}
function wpwand_pro_unlocked_prompt($all_prompts)
{


    return $all_prompts;
}

remove_filter('wpwand_all_prompts', 'wpwand_pro_locked_prompt');
add_filter('wpwand_all_prompts', 'wpwand_pro_unlocked_prompt');


function wpwand_pro_editor_prompts()
{
    $detect_lang = "First, you must need to detect the language of the given paragraph or sentence. And, then generate the result output with the same language. You don\'t need to tell me which language is detected. Only give me the output and nothing else.";

    return [
        [
            'name' => __('Write a paragraph', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to write a paragraph based on that content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Summarize', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to write a summery based that paragraph or sentence. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Expand', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to expand that paragraph or sentence with multiple paragraphs. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        // this will be pro
        [
            'name' => __('Rewrite', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to rewrite that content using better grammar and make it easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Shorter', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to make shorter that content using better grammar and make it easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Longer', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to make longer that content using better grammar and make it easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Better', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to make this content better so that it is easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Simplified', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to make Simplify this sentence so that it is easy to read and understand. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        // [
        //     'name'   => 'Generate ideas',
        //     'prompt' => 'Generate ideas: [text]',
        //     'is_pro' => false,
        // ],
        [
            'name' => __('Make a bullet list', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to make a bullet list based on the content. Do not repeat the same sentence, be creative. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Paraphrase', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to paraphrase that content using better grammar and make it easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Generate a call to action', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to write a high converting call to action based on the content. The CTA should be very persuasive and engaging so that readers feel urgency to take action immediately. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Correct grammar', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to fix any possible grammar mistakes and improve the structure of the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Generate a question', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to generate a meaningful question based on the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Suggest a title', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to create a high converting title based on the content. It should have a hook and high potential to go viral on social media. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Convert to passive voice', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to convert the content to passive voice. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Convert to active voice', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to convert the content to active voice. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Write a conclusion', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to write a nice conclusion based on the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Provide a counterargument', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to write a counterargument based on the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Generate a quote', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to generate a nice and short quote based on the content. It should have a high potential to go viral on social media. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => __('Simplify', 'wp-wand-pro'),
            'prompt' => 'I will give a paragraph or sentence and your job is to make this simplify based on the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
    ];
}

// remove_filter('wpwand_editor_prompts', 'wpwand_editor_prompt');
add_filter('wpwand_editor_prompts', 'wpwand_pro_editor_prompts');


function wpwand_pro_premad_aichars()
{
    return [

        [
            'title' => __('Marketing Content Expert', 'wp-wand-pro'),
            'prompt' => 'You are an expert in digital marketing and content creation, specializing in creating customer-centric narratives. You have helped many businesses before me to showcase their product/service through relatable customer stories.',
        ],

        [
            'title' => __('Persuasive Content Writer', 'wp-wand-pro'),
            'prompt' => 'You are an expert in persuasive marketing specializing in addressing the specific needs and demands of ideal customer personas. You have helped many people before me to create blog posts that effectively convince their target audience to take action.',
        ],

        [
            'title' => __('Problem Solving Writer', 'wp-wand-pro'),
            'prompt' => 'You are an expert in product marketing and customer problem-solving, specializing in creating blog posts that address customer pain points and offer solutions. You have helped many businesses before me to create content that solves their customers\' problems through their product/service.',
        ],

        [
            'title' => __('Business Strategist Expert', 'wp-wand-pro'),
            'prompt' => 'I want you to act as a business strategist expert in market analysis and competitive strategies specializing in customer segmentation. You are an expert in market analysis and competitive strategies specializing in customer segmentation. You have helped many people before me to develop comprehensive plans to understand customer segments and competitors.',
        ],

        [
            'title' => __('Account Executive', 'wp-wand-pro'),
            'prompt' => 'I want you to act as an account executive expert in sales and customer relations specializing in customer acquisition. You are an expert in sales and customer relations specializing in customer acquisition. You have helped many people before me to develop strategies for acquiring new customers.',
        ],

        [
            'title' => __('Content Expert', 'wp-wand-pro'),
            'prompt' => 'You are an expert in content marketing and creation specializing in successful content development. You have helped many people before me to develop successful content.',
        ],

        [
            'title' => __('Customer Support Representative', 'wp-wand-pro'),
            'prompt' => 'You are an expert in customer service and support specializing in customer satisfaction. You have helped many people before me to develop customer service strategies.'
        ]
    ];
}