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
            'name' => 'Write a paragraph',
            'prompt' => 'I will give a paragraph or sentence and your job is to write a paragraph based on that content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Summarize',
            'prompt' => 'I will give a paragraph or sentence and your job is to write a summery based that paragraph or sentence. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Expand',
            'prompt' => 'I will give a paragraph or sentence and your job is to expand that paragraph or sentence with multiple paragraphs. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        // this will be pro
        [
            'name' => 'Rewrite',
            'prompt' => 'I will give a paragraph or sentence and your job is to rewrite that content using better grammar and make it easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Shorter',
            'prompt' => 'I will give a paragraph or sentence and your job is to make shorter that content using better grammar and make it easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Longer',
            'prompt' => 'I will give a paragraph or sentence and your job is to make longer that content using better grammar and make it easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Better',
            'prompt' => 'I will give a paragraph or sentence and your job is to make this content better so that it is easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Simplified',
            'prompt' => 'I will give a paragraph or sentence and your job is to make Simplify this sentence so that it is easy to read and understand. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        // [
        //     'name'   => 'Generate ideas',
        //     'prompt' => 'Generate ideas: [text]',
        //     'is_pro' => false,
        // ],
        [
            'name' => 'Make a bullet list',
            'prompt' => 'I will give a paragraph or sentence and your job is to make a bullet list based on the content. Do not repeat the same sentence, be creative. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Paraphrase',
            'prompt' => 'I will give a paragraph or sentence and your job is to paraphrase that content using better grammar and make it easy to understand. Keep the tone similar.  ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Generate a call to action',
            'prompt' => 'I will give a paragraph or sentence and your job is to write a high converting call to action based on the content. The CTA should be very persuasive and engaging so that readers feel urgency to take action immediately. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Correct grammar',
            'prompt' => 'I will give a paragraph or sentence and your job is to fix any possible grammar mistakes and improve the structure of the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Generate a question',
            'prompt' => 'I will give a paragraph or sentence and your job is to generate a meaningful question based on the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Suggest a title',
            'prompt' => 'I will give a paragraph or sentence and your job is to create a high converting title based on the content. It should have a hook and high potential to go viral on social media. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Convert to passive voice',
            'prompt' => 'I will give a paragraph or sentence and your job is to convert the content to passive voice. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Convert to active voice',
            'prompt' => 'I will give a paragraph or sentence and your job is to convert the content to active voice. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Write a conclusion',
            'prompt' => 'I will give a paragraph or sentence and your job is to write a nice conclusion based on the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Provide a counterargument',
            'prompt' => 'I will give a paragraph or sentence and your job is to write a counterargument based on the content. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Generate a quote',
            'prompt' => 'I will give a paragraph or sentence and your job is to generate a nice and short quote based on the content. It should have a high potential to go viral on social media. ' . $detect_lang . ' My paragraph: [text].',
            'is_pro' => false,
        ],
        [
            'name' => 'Simplify',
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
            'title' => 'Marketing Content Expert',
            'prompt' => 'You are an expert in digital marketing and content creation, specializing in creating customer-centric narratives. You have helped many businesses before me to showcase their product/service through relatable customer stories.',
        ],

        [
            'title' => 'Persuasive Content Writer',
            'prompt' => 'You are an expert in persuasive marketing specializing in addressing the specific needs and demands of ideal customer personas. You have helped many people before me to create blog posts that effectively convince their target audience to take action.',
        ],

        [
            'title' => 'Problem Solving Writer',
            'prompt' => 'You are an expert in product marketing and customer problem-solving, specializing in creating blog posts that address customer pain points and offer solutions. You have helped many businesses before me to create content that solves their customers\' problems through their product/service.',
        ],

        [
            'title' => 'Business Strategist Expert',
            'prompt' => 'I want you to act as a business strategist expert in market analysis and competitive strategies specializing in customer segmentation. You are an expert in market analysis and competitive strategies specializing in customer segmentation. You have helped many people before me to develop comprehensive plans to understand customer segments and competitors.',
        ],

        [
            'title' => 'Account Executive',
            'prompt' => 'I want you to act as an account executive expert in sales and customer relations specializing in customer acquisition. You are an expert in sales and customer relations specializing in customer acquisition. You have helped many people before me to develop strategies for acquiring new customers.',
        ],

        [
            'title' => 'Content Expert',
            'prompt' => 'You are an expert in content marketing and creation specializing in successful content development. You have helped many people before me to develop successful content.',
        ],

        [
            'title' => 'Customer Support Representative',
            'prompt' => 'You are an expert in customer service and support specializing in customer satisfaction. You have helped many people before me to develop customer service strategies.'
        ]
    ];
}