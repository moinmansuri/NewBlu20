<?php

/**
 * Freeform - Language
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @version		4.1.3
 * @filesource	freeform/language/english/freeform_lang.php
 */

$lang = array(

'success'	=>
'Success',

//----------------------------------------
// Required for MODULES page
//----------------------------------------




"freeform_module_name" =>
"Freeform Pro",


'go_pro' =>
"Go Pro!",

"freeform_module_description" =>
"Advanced form creation and data collecting",

'freeform_module_version' =>
'Freeform',

'freeform' =>
"Freeform",

'help' =>
"Help",

'default' =>
"Default",

// -------------------------------------
//	accessory
// -------------------------------------

'freeform_accessory_description' =>
"Recent Freeform entries and short names for fields attached to forms.",

'freeform_form_info' =>
"Form Info",

// -------------------------------------
//	non pro lang
// -------------------------------------

'go_pro_custom_fields' =>
"Did you know that there are more field types available with Freeform Pro? Click here to Go Pro!",

// -------------------------------------
//	fieldtype
// -------------------------------------

'no_available_composer_forms' =>
"No Freeform forms with Composer layouts are available.",

'choose_composer_form' =>
"Choose a Freeform Composer Form to output:",

'toggle_field_short_names' =>
"Toggle Field Short Names",

'show' =>
"Show",

'hide' =>
"Hide",

//----------------------------------------
//  Main Menu
//----------------------------------------

'forms' =>
"Forms",

'fields' =>
"Fields",

'site_id' =>
"Site ID",

'notifications' =>
"Notifications",

'templates' =>
"Templates",

'composer_templates' =>
"Composer Templates",

'permissions' =>
"Permissions",

'utilities' =>
"Utilities",

'preferences' =>
"Preferences",

'export' =>
"Export",

'code_pack' =>
"Code Pack",

'help' =>
"Help",

'online_documentation' =>
"Online Documentation",

'id' =>
"ID",

// -------------------------------------
//	Multi site
// -------------------------------------

'show_from_all_sites' =>
"Show items from all sites",

'use_one_set_of_prefs' =>
"Use one set of preferences for all sites",

'default_show_all_sites' =>
"Show data from all sites by default",

'global_prefs' =>
"Global Preferences",

'site_prefs_for' =>
"Site Prefs For:",

//----------------------------------------
//  Buttons
//----------------------------------------

'save' =>
"Save",

'delete_selected' =>
"Delete Selected",

'create_one_now' =>
"Create one now.",

'dialog_ok' =>
"OK",

'dialog_cancel' =>
"Cancel",

'dialog_continue' =>
"Continue",

'dialog_continue_anyway' =>
"Continue Anyway",

"yes" =>
"Yes",

"no" =>
"No",

'notice' =>
"Notice",

'view_lower' =>
"view",

// -------------------------------------
//	form statuses
// -------------------------------------

'pending' =>
"Pending",

'open' =>
"Open",

'closed' =>
"Closed",

'status' =>
"Status",

// -------------------------------------
// forms
// -------------------------------------

'no_forms' =>
"No Forms currently exist.",

'no_forms_legacy' =>
"No Forms currently exist, however, since you upgraded from a previous version of Freeform, you can migrate your old collections now.",

'migrate_collections' =>
"Migrate your old collections",

'create_new_form_now' =>
"Create a new form now.",

'forms' =>
"Forms",

'form' =>
"Form",

'submissions' =>
"Submissions",

'pending_submissions' =>
"Pending Submissions",

'moderate' =>
"Moderate",

'actions' =>
"Actions",

'edit' =>
"Edit",

'in_composer' =>
"in composer",

'settings' =>
"Settings",

'duplicate' =>
"Duplicate",

'delete' =>
"Delete",

// -------------------------------------
//	create forms
// -------------------------------------

'edit_form_success' =>
"Form Saved",

'duplicated_from_' =>
"Inserted data duplicated from:",

'create_form' =>
"Create Form",

'update_form' =>
"Update Form",

'create_form_description' =>
"Fill out the basic information for a Form below. You can then choose to build your form in the Composer drag and drop interface, or in standard ExpressionEngine templates with Freeform tags.",

'new_form' =>
"New Form",

'form_label' =>
"Form Label",

'form_label_desc' =>
"This is the full name of the Form.<br/>Example: Contact Us",

'form_name' =>
"Form Name",

'form_name_desc' =>
"Short name of the form. Single word, no spaces, underscores allowed. Example: contact_us",

'form_description_desc' =>
"Describe the form.<br/>Helpful for keeping track of the purpose of the form.",

'default_status' =>
"Default Status",

'default_status_desc' =>
"All form submissions will be set to this status unless overridden in the tag params. A status of pending (default)",

'design_template' =>
"Design Template",

'design_template_desc' =>
"Determines the look of the form.",

'enable' =>
"Enable",

'notify_admin' =>
"Notify Admin",

'notify_user' =>
"Notify User",

'notify_admin_desc' =>
"Enable admin notifications to automatically email the site admin when this form gets a submission.",

'notify_user_desc' =>
"User notifications require a field that is accepting an email as input. If the user inputs an email, then they will be notified of their submission. If no fields are currently available, you can edit this field later to add one or override the field name in the template parameters.",

'user_email_field' =>
"User Email Field",

'choose_user_email_field' =>
"Choose User Email Field",

'notification_template' =>
"Notification Template",

'user_notification' =>
"User Notification Template",

'user_notification_desc' =>
"Email notification template for the person who fills the form out (if email is given).",

'admin_notification' =>
"Admin Notification Template",

'admin_notification_desc' =>
"Email notification template that goes to the site administrators when a form is filled out.",

'continue' =>
"Continue",

'continue_desc' =>
"Save this form and continue to composer to build it.",

'save' =>
"Save",

'save_desc' =>
"Build this form later in an ExpressionEngine template.",

'admin_notification_email' =>
"Admin Notification Email Address",

'admin_notification_email_desc' =>
"Email addresses to send the admin notification emails to when a form is filled out. Separate emails with commas.",

'composer_form_type_desc' =>
"Build this form in a drag and drop interface",

'template_form_type_desc' =>
"Code this form in an ExpressionEngine template",

'composer' =>
"Composer",

'template' =>
"Template",

'form_type' =>
"Form Type",

'form_fields' =>
"Form Fields",

'form_fields_desc' =>
"You need to include form fields with each form in order to collect data.",

'auto_generate_name' =>
"Auto Generate Name",

'click_drag_add_remove_sort'=>
"Click or drag to add and remove fields. Drag the fields in the right column to sort. The fields in the right column will be available in this form on output and they will display in this order when using the {freeform:all_form_fields} tag pair.",

'return_page_field' =>
"Enter a Return path to send users to once they have submitted this form (ex: 'form/thank_you'):",

// -------------------------------------
//	composer
// -------------------------------------

'composer_instructions' =>
"Click or drag any of the special fields or custom fields into the composer view. Clicking an element creates a row with the element inside. Create your own row and you can drag elements into it. The page break element makes this a multipage form.",

'insert' =>
"Insert",

'save_and_finish'=>
"Save and Finish",

'click_or_drag_to_add'=>
"click or drag to add",

'title'=>
"Title",

'paragraph'=>
"Paragraph",

"page" =>
"Page",

'page_break'=>
"Page Break",

'page_name'=>
"Page Name",

'multipage'=>
"Multipage",

'submit_button'=>
"Submit Button",

'submit_previous_button'=>
"Submit Previous Button",

'search_fields'=>
"Search Fields",

'finished' =>
"Finished",

'row'=>
"Row",

'captcha' =>
"Captcha",

'sticky_controls' =>
"Sticky Controls",

'double_click_to_edit' =>
"Double Click to Edit",

'double_click_to_edit_recipients' =>
"Double Click to Edit Recipients",

'notify_friends' =>
"Notify Your Friends",

'notify_instructions' =>
"Insert your friends' emails, comma separated:",

'dynrec_edit_instructions' =>
"Put email addresses in column 1 and the names that will be visible in column 2.",

'dynrec_output_label' =>
"Dynamic Recipients Output Label",

'select_dropdown' =>
"Select Dropdown",

'checkbox_group' =>
"Checkbox Group",

'dynrec_output_type' =>
"Dynamic Recipients Output Type",

//lower case on purpose
'delete_lower' =>
"delete",

//lower case on purpose
'delete_row_lower' =>
"delete row",

//lower case on purpose
'delete_field_lower' =>
"delete field",

//lower case on purpose
'require_field_lower' =>
"require field",

//lower case on purpose
'unrequire_field_lower' =>
"unrequire field",

//lower case on purpose
'add_column' =>
"add column",

//lower case on purpose
'remove_column' =>
"remove column",

'preview' =>
"Preview",

'allowed_html_tags' =>
"Allowed html tags: ",

'captcha_input_instructions' =>
"Please enter the word you see in the image below:",

'composer_data_saved' =>
"Composer Data Saved",

'composer_preview' =>
"Composer Preview",

'missing_submits' =>
"One or more of the pages of your form does not have a submit button. This could cause problems for people submitting your form.",

'clear_all' =>
"Clear All",

'clear_all_rows' =>
"Clear All Rows",

'clear_all_warning' =>
"Are you sure you want to clear all items from Composer? You cannot undo this action.",

// -------------------------------------
//	view/moderate entries
// -------------------------------------

'entries' =>
"Entries",

'complete' =>
"Complete",

'entry_id' =>
"Entry&nbsp;ID",

'author_id' =>
"Author&nbsp;ID",

'author' =>
"Author",

'ip_address' =>
"I.P.&nbsp;Address",

'entry_date' =>
"Entry&nbsp;Date",

'edit_date' =>
"Edit&nbsp;Date",

'status' =>
"Status",

'never' =>
"Never",

'n_a' =>
"N/A",

'num_items_awaiting_moderation' =>
"You have %num% &quot;<strong>%form_label%</strong>&quot; submissions awaiting moderation.",

'awaiting_moderation' =>
"Awaiting Moderation",

'submit' =>
"Submit",

'submit_previous' =>
"Submit Previous",

'previous' =>
"Previous",

'view' =>
"View",

'no_entries_for_form' =>
"There are no entries for this form.",

'edit_field_layout' =>
"Edit Field Layout",

'layout_saved' =>
"Layout Saved.",

'keywords' =>
"Keywords",

'today' =>
"Today",

'this_week' =>
"This Week",

'this_month' =>
"This Month",

'last_month' =>
"Last Month",

'this_year' =>
"This Year",

'choose_date_range' =>
"Choose Date Range",

'start_date' =>
"Start Date",

'end_date' =>
"End Date",

'moderate' =>
"Moderate",

'form_field' =>
"Form Field",

'approve' =>
"Approve",

'entries_approved' =>
"Entry(s) Approved",

'no_entries_awaiting_approval' =>
"No entries awaiting approval",

'no_results_for_search' =>
"No results for this search",

'viewing_moderation' =>
"You are viewing submissions for  &quot;<strong>%form_label%</strong>&quot;. These are status &quot;pending&quot;. Approving them will set the status to &quot;open&quot;.",

'approve_selected' =>
"Approve Selected",

'edit_selected' =>
"Edit Selected",

'delete_selected' =>
"Delete Selected",

'entries_deleted' =>
"Entry(s) Deleted",

'confirm_delete_entries' =>
"Are you sure you want to permanently delete the data?",

'must_select_group' =>
"In order to save this layout, you must assign it to one or more groups below.",

'download_started' =>
"Your export is building and will download when finished.",

// -------------------------------------
//	edit entries
// -------------------------------------

'new_entry' =>
"New Entry",

'edit_entry' =>
"Edit Entry",

'group_title' =>
"Group Title",

'screen_name' =>
"Screen Name",

'guest' =>
"Guest",

'edit_entry_success' =>
"Entry Successfully Edited",

'new_entry_success' =>
"New Entry Created",

// -------------------------------------
//	column prefs
// -------------------------------------

'edit_field_layout' =>
"Edit Field Layout",

'hidden_fields' =>
"Hidden Fields",

'shown_fields' =>
"Shown Fields",

'click_to_add' =>
"click to add",

'drag_to_reorder' =>
"drag to reorder",

'save_this_layout_for' =>
"Save this layout for",

'just_me' =>
"Just Me",

'everyone' =>
"Everyone",

// -------------------------------------
//	sub layouts
// -------------------------------------

'field_entry_view' =>
"Entries",

// -------------------------------------
//	export
// -------------------------------------

'export' =>
"Export",

'txt' =>
"Text",

'xml' =>
"XML",

'csv' =>
"CSV",

'json' =>
"JSON",

'export_as' =>
"Export as",

'export_entries' =>
"Export Entries",

'shown_fields' =>
"Shown Fields",

'all_fields' =>
"All Fields",

'format_dates' =>
"Format&nbsp;Dates",

// -------------------------------------
//	multi-item rows for fields
// -------------------------------------

'multi_list_items' =>
"Multi-List Items",

'multi_list_items_desc' =>
"Choose from 4 types of multi-select lists:<br/><br/> - List<br/> - Value/Label Pair list<br/> - List From Channel Field<br/> - Newline Delimited Textarea<br/><br/>New blank inputs will create themselves when the last one is used.",

'load_from_channel_field' =>
"Load From Channel Field",

'title' =>
"Title",

'url_title' =>
"URL Title",

'value_label_list' =>
"Value/Label List",

'list_type' =>
"List type",

'list' =>
"List",

"nld_textarea" =>
"Newline Delimited Textarea",

'type_list_desc' =>
"This is a simple list of values. Put each value in one field.",

'type_value_label_desc' =>
"This is a value/label list. The value is the data that is stored for the choice and the label is what's shown as choices to users.",

'channel_field_list_desc' =>
"This will take all data from a single channel field and make a list from it.",

'type_nld_textarea_desc' =>
"This is a Newline Delimited Textarea. This will make a list item from each non-empty line in the list.",

// -------------------------------------
//	show fields
// -------------------------------------

'type' =>
"Type",

'label' =>
"Label",

'name' =>
"Name",

'description' =>
"Description",

// -------------------------------------
//	field types
// -------------------------------------

'fieldtypes' =>
"Field types",

'no_fieldtypes' =>
"No field types available.<br/>What, you deleted all of the default ones, too? O_o",

'no_fieldtypes_submitted' =>
"No Field types submitted.",

'following_fields_converted' =>
"The following fields will be converted to the default type of 'text' with this un-installation",

'freeform_field_description' =>
"These field types are exclusively for Freeform and are not the same as ExpressionEngine custom field types.",

'freeform_fieldtype_docs' =>
"Freeform allows third party developers to make their own field types, much like native ExpressionEngine field types.",

'freeform_fieldtype_url_desc' =>
"View the Freeform Field type development documentation.",

'freeform_fieldtype_name' =>
"Freeform Field type Name",

'description' =>
"Description",

'version' =>
"Version",

'status' =>
"Status",

'action' =>
"Action",

'install' =>
"Install",

'uninstall' =>
"Uninstall",

'not_installed' =>
"Not Installed",

'installed' =>
"Installed",

'fieldtype_installed' =>
"Field type Installed",

'fieldtype_uninstalled' =>
"Field type Uninstalled",

'fieldtype_install_error' =>
"There was an unknown error attempting to install this field type.",

// -------------------------------------
//	default field types
// -------------------------------------

// -------------------------------------
//	Checkbox
// -------------------------------------

'default_checkbox_name' =>
"Checkbox",

'default_checkbox_desc' =>
"A field with a single checkbox with \"y\" or \"n\" options.",

// -------------------------------------
//	Checkbox Group
// -------------------------------------

'default_checkbox_group_name' =>
"Checkbox Group",

'default_checkbox_group_desc' =>
"A field that contains a group of checkboxes for multiple choices.",

// -------------------------------------
//	country select
// -------------------------------------

'default_country_name' =>
"Country Select",

'default_country_desc' =>
"A dropdown selection of countries. Loaded from ./system/expressionengine/config/countries.php",

// -------------------------------------
//	country select
// -------------------------------------

'default_hidden_name' =>
"Hidden Field",

'default_hidden_desc' =>
"A hidden field for collecting information the user does not need to interact with.",

'default_hidden_field_data' =>
'Default Incoming Data',

'default_hidden_field_data_desc' =>
"Use this to set the default data that will be set to the hidden field's value.<br/><br/>Allowed data are ExpressionEngine tags and brackets and special fields. All HTML will be removed from this setting.<br/><br/>Use CURRENT_URL to retrive the URL the user was on when they submitted the page. Due to the restrictions of PHP, any data after hash tags 'http://site.com/#item' is not retrievable.",

'hidden_field_not_shown' =>
"Hidden Field: This will not be shown on the font end.",

// -------------------------------------
//	mailinglist field
// -------------------------------------

'default_mailinglist_name' =>
"Mailinglist",

'default_mailinglist_desc' =>
"A field that allow users to subscribe to ExpressionEngine Mailing List module lists.",

'invalid_mailinglist' =>
"Invalid Mailinglist",

'no_mailinglists' =>
"There are no mailinglists available.",

'opt_in_by_default' =>
"Opt in by default",

'opt_in_by_default_desc' =>
"By default, check mailinglist fields when they appear in forms.",

'show_multiple_lists' =>
"Show multiple lists",

'show_multiple_lists_desc' =>
"Show all of the mailinglists in a group of checkboxes.",

'mailinglist_send_email_confirmation' =>
"Send email confirmation",

'mailinglist_send_email_confirmation_desc' =>
"Send an email with a link to allow users to confirm before joining a list.",

'show_lists_by_default' =>
"Show lists",

'show_lists_by_default_desc' =>
"Show only these lists.",

// -------------------------------------
//	File Upload
// -------------------------------------

'default_file_name' =>
"File Upload",

'default_file_desc' =>
"A field that allows a user to upload files.",

'file_upload_location' =>
"File Upload Location",

'file_upload_location_desc' =>
"Where should the files be uploaded? You can use the standard EE upload directory configuration to specify new upload paths.",

'file_upload_missing_error' =>
"An upload location is required for the file upload field to function.",

'invalid_file_upload_preference' =>
"Invalid file upload preference id.",

'file_upload_pref_missing_error' =>
"You have no file upload preferences set and you need at least one location so you can upload files with this field.",

'specify_upload_location' =>
"Specify my own upload location",

'full_path_to_folder' =>
"Full path to writable folder",

'system_information' =>
"System Information",

'system_information_desc' =>
"This is helpful information from your php.ini settings. These cannot be changed in this fields options, but is changed in your php.ini file.",

'max_file_upload_size' =>
"Maximum single file upload size:",

'max_files_uploadable' =>
"Maximum files that can be uploaded in a single submission:",

'allowed_upload_count' =>
'Allowed upload count',

'allowed_upload_count_desc' =>
"Maximum quantity of files that can be uploaded in a single submission.<br/><br/><strong class='ss_notice'>The maximum number in this drop-down is the PHP system settings for maximum files that can be uploaded in a single submission and cannot be overridden by using multiple file field types.</strong>",

'overwrite_on_edit' =>
'Overwrite On Edit',

'overwrite_on_edit_desc' =>
'When editing an entry and new files are uploaded, replace the previous set of uploaded files with the new.',

'disable_xss_clean' =>
"Disable XSS Clean",

'disable_xss_clean_desc' =>
"This option allows you to disable system XSS cleaning just for this file upload field in case you are having issues with user uploads being incorrectly blocked.",

'file_field_uploads' =>
"File Field Uploads",

'no_files_uploaded' =>
"No Files Uploaded",

//file size abbreviation for KiloBytes
'kb' =>
"KB",

'filesize' =>
'File Size',

'filename' =>
'File Name',

'download' =>
'Download',

'front_end_link' =>
'Front End Link',

'file_location' =>
'File Location',

'files_deleted' =>
"File(s) Deleted",

'view_files' =>
"View Files",

'allowed_file_types' =>
"Allowed File Types",

'allowed_file_types_desc' =>
"Which file types would you like to allow? Put the file extension of each acceptable filetype separated by a pipe. e.g. 'jpg|png|gif'. Simply putting a star, '*', will allow all files types available.",

'email_attachments' =>
"Email Attachments",

'email_attachments_desc' =>
"Attach the file to email notifications? There are 4 times of notifications.<br/><br/> - Admin<br/> - User<br/> - Dynamic Recipients<br/> - User Inputted Recipients<br/>",

'dynamic_recipients' =>
"Dynamic Recipients",

'user_recipients' =>
"User Recipients",

'cannot_find_file' =>
"Cannot find file",

'upload_preference_name' =>
"Upload Preference Name",

// -------------------------------------
//	MultiSelect
// -------------------------------------

'default_multiselect_name' =>
"Multiselect",

'default_multiselect_desc' =>
"A field that has a list of items that can have multiple selections.",

// -------------------------------------
//	Province select
// -------------------------------------

'default_provinces_name' =>
"Province Select",

'default_provinces_desc' =>
"A dropdown selection of Canadian provinces and territories. Loaded from Freeform language file.",

// -------------------------------------
//	Radio
// -------------------------------------

'default_radio_name' =>
"Radio Buttons",

'default_radio_desc' =>
"A field of single choice options with radio buttons.",

// -------------------------------------
//	Select
// -------------------------------------

'default_select_name' =>
"Select",

'default_select_desc' =>
"A field that shows a dropdown list of choices.",

// -------------------------------------
//	State Select
// -------------------------------------

'default_state_name' =>
"State Select",

'default_state_desc' =>
"A dropdown selection of US states and territories. Loaded from Freeform language file.",

// -------------------------------------
//	Text Input
// -------------------------------------

'default_text_name' =>
"Text",

'default_text_desc' =>
"A field for single line text input.",

'integer' =>
"Integer",

'decimal' =>
"Decimal",

'number' =>
"Number",

'email' =>
"Email",

'any' =>
"Any",

'field_content_type' =>
"Field Content Type",

'field_content_type_desc' =>
"Allows you to set a validation type for this text area.",

// -------------------------------------
//	Textarea
// -------------------------------------

'default_textarea_name' =>
"Textarea",

'default_textarea_desc' =>
"A field for multi-line text input.",

'textarea_rows' =>
"Textarea Rows",

'textarea_rows_desc' =>
"Default row amount to display.",

'disallow_html_rendering' =>
"Disallow HTML Rendering",

'disallow_html_rendering_desc' =>
"By default, HTML tags will be encoded so that they will not render on output. This helps prevent users from inputting html and showing images or their own custom output on your pages. Disabling this will allow html to be rendered on output",

//----------------------------------------
//  edit field
//----------------------------------------

'edit_field_success' =>
"Field Saved",

'update_field' =>
"Update Field",

'create_field' =>
"Create Field",

'fields_deleted' =>
"The indicated fields have been deleted.",

'new_field' =>
"New Field",

'fields' =>
"Fields",

'field' =>
"Field",

'field_type' =>
"Field Type",

'field_label' =>
"Field Label",

'field_label_desc' =>
"The full name of the field. Example: First Name.",

'field_name' =>
"Field Name",

'field_name_desc' =>
"Short name of the field. One word, no spaces, underscores allowed. Example: first_name",

'field_order' =>
"Field Order",

'field_order_desc' =>
"Field Order",

'field_length' =>
"Field Length",

'field_length_desc' =>
"Maximum length of inputed data.",

'field_display_options' =>
"Field Display Options",

'field_display_options_desc' =>
"Choose if this field should display by default throughout the module. You can further customize field layouts per form, per user group on the submissions and moderation page.",

'editable' =>
"Field is editable?",

'submissions_page' =>
"Show field on submissions CP page?",

'moderation_page' =>
"Show field on moderation CP page?",

'composer_use' =>
"Allow field to be used in Freeform Composer?",

'field_description' =>
"Field Description",

'field_description_desc' =>
"Describe the field. You can use this to keep track of the use of the field or this description can be inserted into your forms.",

'field_edit_instructions' =>
"Enter/Update Field Information below. You can use this field in Freeform Composer or templates with the Freeform template tags.",

'generate' =>
"Generate",

'field_options' =>
"Field Options",

'filter_entries' =>
"Filter Entries",

'add_to_forms' =>
"Add To Form(s)",

'add_to_freeform_desc' =>
"Add this field to forms on save. Forms that this field is already in are on the right.",

'add_to_freeform_notice' =>
"If you chose to remove this field from any forms, it will also remove any and all data associated with the field from that form!",

// -------------------------------------
//	notifications
// -------------------------------------

'no_notifications' =>
"No Notifications currently exist.",

'user' =>
"User",

'admin' =>
"Admin",

'notification_edit_warning' =>
"The following forms currently use this notification: <strong>%form_names%</strong>",

'new_notification' =>
"New Notification",

'update_notification' =>
"Update Notification",

'create_notification' =>
"Create Notification",

'allow_html' =>
"Allow HTML",

'wordwrap' =>
"Word Wrap",

'formatting_options' =>
"Formatting Options",

'notification_label' =>
"Notification Label",

'notification_label_desc' =>
"The full name of the Notification. Example: Submission Success",

'notification_name' =>
"Notification Name",

'notification_name_desc' =>
"The short name of the Notification.<br/>Single word, no spaces, underscores allowed.<br/>Example: submission_success",

'from_email' =>
"From Email",

'from_email_desc' =>
"The email address that will appear in the 'From' or your notification email.",

'from_name' =>
"From Name",

'from_name_desc' =>
"The name that will appear in the 'From' or your notification email.",

'reply_to_email' =>
"Reply To Email",

'reply_to_email_desc' =>
"This is the email address that the recipient will reply to when receiving this notification.",

'email_subject' =>
"Subject",

'email_subject_desc' =>
"The subject line of the notification. The following variables are available: {my_custom_field}, {freeform_entry_id}, {entry_date}, {form_name}, {form_id}, {form_label}<br/><br/><span class='note' Check with your hosting company about maximum email subject length as it could cause emails to silently fail to send.",

'notification_description' =>
"Description",

'notification_description_desc' =>
"Describe the Notification.<br/>Helpful for keeping track of the purpose of the Notification.",

'notification_edit_instructions' =>
"These are templates that are used to format and generate email notifications to admins and/or users. Once you have created a notification template, you specify it when creating/editing forms in the control panel, or override it in your EE templates.",

'email_message' =>
"Email Message",

'email_message_desc' =>
"",

'edit_notification_success' =>
"Notification Saved",

'click_insert_custom_fields' =>
"Click to insert your custom fields",

'click_insert_standard_tags' =>
"Click to insert standard tags",

'search' =>
"Search",

'default_notification' =>
"Default Notification",

'default_notification_subject' =>
"Someone has filled out form: {form_label}",

'default_notification_template' =>
"Someone has filled out form: {form_label}
Here are the details:

{all_form_fields_string}",

'include_attachments' =>
"Include Attachments",

'include_attachments_desc' =>
"Some Freeform fields allow file uploads. Enabling attachments emails those uploaded files with the notifications.",

'uploads' =>
"Uploads",

'upload_count' =>
"Upload count",

'freeform_file_field_upload_count' =>
"Freeform File Field Upload Count",

'attachments' =>
"Attachments",

'attachment' =>
"Attachment",

'confirm_delete_notification' =>
"Are you sure you want to permanently delete the notification?",

// -------------------------------------
//	templates
// -------------------------------------

'confirm_delete_template' =>
"Are you sure you want to permanently delete this Composer Template?",

'no_templates' =>
"No custom Composer templates currently exist.",

'template_edit_warning' =>
"The following forms currently use this Composer Template: <strong>%form_names%</strong>",

'new_template' =>
"New Composer Template",

'update_template' =>
"Update Template",

'create_template' =>
"Create Template",

'template_label' =>
"Composer Template Label",

'template_label_desc' =>
"The full name of the Composer Template. Example: Blog Form Template",

'template_name' =>
"Composer Template Name",

'template_name_desc' =>
"The short name of the Composer Template.<br/>Single word, no spaces, underscores allowed.<br/>Example: blog_form_template",

'template_description' =>
"Composer Template Description",

'template_description_desc' =>
"Describe the Composer Template.<br/>Helpful for keeping track of the purpose of the Composer Template.",

'template_edit_instructions' =>
"This allows you to create style templates for your Composer-based forms. You can control the way the output, styling, and formatting is handled.",

'edit_template_success' =>
"Composer Template Saved",

'delete_template_success' =>
"Composer Template(s) Deleted",

'default_template' =>
"Default Composer Template",

'enable_template' =>
"Enable Composer Template",

'composer_template' =>
"Composer Template",

'quick_save' =>
"Quick Save",

'template_params' =>
"Template Params",

'param_name' =>
"Param Name",

'param_value' =>
"Param Value",

'template_params_desc' =>
"Add default tag params composer output. These can be any parameters available to {exp:freeform:form}. <div class='ss_notice'>These will override anything set by Freeform Composer.</div>",

'template_name_exists' =>
"A Composer Template by that name already exists.",

//----------------------------------------
//  Utilities
//----------------------------------------

'collections' =>
"Collections",

'collections_desc' =>
"These collections have entries that have not yet been migrated to Freeform 4.",

'empty_fields' =>
"Empty Fields",

'migrate_empty_fields' =>
"Yes, migrate empty fields",

'migrate_empty_fields_desc' =>
"It is most likely not necessary to migrate fields that have never contained data.",

'migrate_attachments' =>
"Yes, migrate attachments",

'migrate_attachments_desc' =>
"You may have allowed people submitting your forms to attach files to their submissions. You can migrate those attachments to your new forms. Custom file upload fields will be created for you.",

'migrate_attachments_desc_not_installed' =>
"You may have allowed people submitting your forms to attach files to their submissions. You can migrate those attachments to your new forms. Custom file upload fields will be created for you. However, to do so, you must install the File Upload Freeform field type first.",

'migration_in_progress' =>
"Migration in progress",

'migration_complete' =>
"Migration complete",

'nothing_to_migrate' =>
"There are no collections to migrate.",

'no_collections' =>
"No collections were found for migration.",

'empty_form_name' =>
"The form name was empty.",

'missing_data_for_field_creation' =>
"Unable to create a field due to some missing data.",

// -------------------------------------
// 	Language for permissions
// -------------------------------------

"permission" =>
"permission",

"save_permissions" =>
"Save Permissions",

'permissions_updated' =>
"Permissions Updated",

'allow_all' =>
"Allow All",

'deny_all' =>
"Deny All",

'by_group' =>
"By Group",

'allow' =>
"Allow",

'deny' =>
"Deny",

'permissions_description' =>
"These permissions allow and disallow member groups from viewing certain tabs in the module control panel. If a group is denied from a tab and attempts to access it manually they will be redirected. If the group has permission to no tabs, they will be redirected to the ExpressionEngine Control Panel home page. Super Admins always have access regardless of these settings.",

'global_permissions_description' =>
"By checking global permissions, you will set one set of permissions for all sites.",

'use_global_permissions' =>
"Use Global Permissions",

'default_permissions_new_group' =>
"Default Permissions For New Groups",

'default_permissions_new_group_desc' =>
"Set the default permission for newly added member groups for any pages set to 'By Group'.",

'mcp_tab_permissions' =>
"Module Control Panel Tab Permissions",

// -------------------------------------
// 	Language for preferences
// -------------------------------------

"preferences" =>
"Preferences",

"preference" =>
"Preference",

"value" =>
"Value",

"save_preferences" =>
"Save Preferences",

'preferences_updated' =>
"Preferences Updated",

'use_solspace_mcp_style' =>
"Use Solspace Control Panel Style",

'use_solspace_mcp_style_desc' =>
"Custom Solspace Control Panel UI designed by <a href='http://ericmillerdesign.com/' target='_blank'>Eric Miller Design</a>. <br/>Works on Chrome, Safari, Firefox, and Internet Explorer 9+.",

'censor_using_ee_word_list' =>
"Use ExpressionEngine Word Censoring",

'censor_using_ee_word_list_desc' =>
"",

'spam_keyword_ban_enabled' =>
"Ban keywords in fields",

'spam_keyword_ban_enabled_desc' =>
"Checks inputs against the keywords set below and denies the submission if the keywords are found.",

'spam_keywords' =>
"Ban Keywords",

'spam_keywords_desc' =>
"Separate with newline, use asterisk for wild card.",

'spam_keyword_ban_message' =>
"Keyword Ban Message",

'spam_keyword_ban_message_desc' =>
"The message shown to users when banned keywords are submitted.",

'form_statuses' =>
"Custom Form Statuses",

'form_statuses_desc' =>
"Custom statuses for form entries beside the defaults of: Pending, Open, Closed.",

'max_user_recipients' =>
'Maximum User Recipients',

'max_user_recipients_desc' =>
'Maximum amount of recipients for user inputed email contacts. If the maximum is exceeded, an error is shown to the user.',

'spam_count' =>
'Spam Count',

'spam_count_desc' =>
'Maximum emails per IP Address within the Spam Interval time period.',

'spam_interval' =>
'Spam interval',

'spam_interval_desc' =>
'Time interval reset for maximum emails (Spam Count) sent (in Minutes)',

'allow_user_field_layout' =>
"Allow User Field Layouts",

'allow_user_field_layout_desc' =>
"Allow users to adjust their own field layout preferences in the entries area rather than having a global set defined by admins.",

'enable_spam_prevention' =>
"Enable Spam Prevention",

'enable_spam_prevention_desc' =>
"Enable Spam Prevention based on the Spam Count and Spam Interval preferences.",

'default_show_all_site_data' =>
"Show Data from All Sites",

'default_show_all_site_data_desc' =>
"Show data from all sites by default. This will not stop Freeform from being site aware, but will show all Freeform, Entries, Fields, and Notifications templates from all sites by default instead of having to enable it on a per menu tab basis.",

'keep_unfinished_multi_form' =>
"Keep Unfinished Multi-page Form Data",

'keep_unfinished_multi_form_desc' =>
"Multi-page form entries that are not completed within the above specified time range will be deleted. If you wish to prevent that automatic deletion and keep partial multi-page form submissions, check this preference. <br/><br/><span class='ss_notice'>If you choose to enable this, it is STRONGLY recommended that you include, in your website's public privacy notice, that you are storing data from incomplete forms. This can have serious privacy implications.</span>",

'multi_form_timeout' =>
"Multi-page Form Completion Timeout",

'multi_form_timeout_desc' =>
"Number of seconds until a multi-page form cookie/entry is marked for deletion. The timer gets reset each time the user submits a page of data in the multi-page form. (e.g. 3 hours = 7200 = 60 * 60 * 3).",

'all_sites' =>
"All Sites",

'cp_date_formatting' =>
"Control Panel Date Formatting",

'cp_date_formatting_desc' =>
"General date formatting setting for items in the Freeform Control Panel. See the <a href='http://php.net/manual/en/function.date.php#refsect1-function.date-parameters' target='_blank'>PHP Date Format Manual</a> for available options.",

'hook_data_protection' =>
"Hook Data Protection",

'hook_data_protection_desc' =>
"Sometimes third party or custom extensions using Freeform's hooks might forget or malform needed data. This can cause Freeform to behaive in mysterious ways. With this enabled, Freeform will check for data integrity when calling hooks and use backup data if the incoming data from the extension is malformed.<br/><br/><span class='ss_notice'>Only disable this if you are having trouble with extensions to Freeform functioning.</span>",

'disable_missing_submit_warning' =>
"Disable Missing Submit Warning",

'disable_missing_submit_warning_desc' =>
"When a submit button is missing from Composer, a warning is shown letting you know and allowing you to continue or go back and add a submit button before submitting. This setting allows you to disable the warning.",

// -------------------------------------
//	global preferences
// -------------------------------------

'prefs_all_sites' =>
"Use These Preferences for All Sites",

'prefs_all_sites_desc' =>
"Use one set of preferences for all sites. If this is unchecked, each site will have its own preferences. If it is checked, every site will use the preferences from site 1.",

// -------------------------------------
//	delete confirmations
// -------------------------------------

'delete_form_confirmation' =>
"Are you sure you want to delete the form(s) and all form entries?",

'delete_field_confirmation' =>
"Are you sure you want to delete the fields(s) and all data associated with them?",

'action_cannot_be_undone' =>
"This action cannot be undone.",

'delete_form_success' =>
"Successfully deleted form(s)",

'delete_field_success' =>
"Successfully deleted field(s)",

'delete_entry_success' =>
"Successfully deleted entry(s)",

'delete_notification_success' =>
"Successfully deleted notification(s)",

'freeform_will_lose_data' =>
"The following forms will lose column data if these fields are deleted (sorted by field to be deleted):",

//----------------------------------------
//  Errors
//----------------------------------------

'missing_post_data' =>
"Missing required valid POST variable",

'call_to_undefined_method' =>
"Fatal error: Call to undefined method %class%::%method%()",

'unable_to_write_to_cache' =>
"Unable to write to cache directory. Make sure that your cache directory is properly set and is writable.",

'export_error' =>
"An uncaught error occurred when trying to export.",

'email_subject_required' =>
"Email subject required.",

'email_limit_exceeded' =>
"You have exceeded the maximum amount of emails allowed to be sent via this form within a pre-determined time period.",

'invalid_user_email_field' =>
"Invalid User Email Field",

'no_valid_recipient_emails' =>
"Recipient Emails were submitted, but none were valid.",

'over_recipient_user_limit' =>
"You have inputted more recipients than are allowed.",

'over_recipient_limit' =>
"You have selected more recipients than are allowed.",

'invalid_upload_count' =>
"Invalid upload count: The max upload count must be greater than 0 and less than or equal to your php.ini's max_file_uploads setting.",

'invalid_custom_location' =>
"Invalid Custom File Upload Location: This must be an absolute path to a <em>writable</em> folder on your server.",

'invalid_upload_location' =>
"Invalid upload location choice. It's possible that upload locations were edited while you were updating.",

'invalid_filetype_filter' =>
"Invalid Filetype Filter: Valid file type filters are either '*' (without quotes) for allow all or pipe delimited lists of file extensions like 'jpeg|jpg|gif|png'.",

'file_upload_limit_exceeded' =>
"More files were attempted to be uploaded than the set limit.",

'unknown_file_upload_problem' =>
"Unknown file upload problem.",

//sub reasons
'reason_banned' =>
"Reason: Banned",

'reason_ip_required' =>
"Reason: IP Required",

'reason_secure_form_timeout' =>
"Reason: Secure Forms Timeout",

'no_form_ids_submitted' =>
"No form ids were submitted.",

'no_field_ids_submitted' =>
"No field ids were submitted.",

'invalid_form_id' =>
"Invalid form id(s) submitted.",

'invalid_field_id' =>
"Invalid field id(s) submitted.",

'invalid_entry_id' =>
"Invalid entry id(s) submitted.",

'invalid_notification_id' =>
"Invalid notification id(s) submitted.",

'invalid_composer_data' =>
"Invalid Composer Data Submitted.",

'non_valid_email' =>
"%email% is not a valid email.",

'invalid_entry_data' =>
"Invalid Entry Data",

'no_fields' =>
"No fields currently exist.",

'field_name_can_only_contain' =>
"Field names can only contain underscores, dashes, letters, and numbers, have at least one letter, and be lowercase.",

'form_name_can_only_contain' =>
"Form names can only contain underscores, dashes, letters, and numbers, have at least one letter, and be lowercase",

'notification_name_can_only_contain' =>
"Notification names can only contain underscores, dashes, letters, and numbers, have at least one letter, and be lowercase",

'template_name_can_only_contain' =>
"Template names can only contain underscores, dashes, letters, and numbers, have at least one letter, and be lowercase",

'duplicate_field_name' =>
"There is already a field with the name '%name%'.",

'duplicate_form_name' =>
"There is already a field with the name '%name%'.",

'duplicate_notification_name' =>
"There is already a field with the name '%name%'.",

'duplicate_template_name' =>
"There is already a field with the name '%name%'.",

'field_name_required' =>
"A field name is required and cannot be blank or a number.",

'field_label_required' =>
"A field label is required and cannot be blank.",

'template_label_required' =>
"A template label is required and cannot be blank.",

'notification_name_required' =>
"A notification name is required and cannot be blank or a number.",

'template_name_required' =>
"A template name is required and cannot be blank or a number.",

'notification_label_required' =>
"A notification label is required and cannot be blank.",

'form_name_required' =>
"A form name is required and cannot be blank or a number.",

'form_label_required' =>
"A form label is required and cannot be blank.",

'field_edit_warning' =>
"This could change existing data!",

'field_edit_warning_desc' =>
"The following forms, and their data, will be affected when editing this field: <strong>%form_names%</strong>",

"field_name_exists" =>
"A field by the name '%name%' already exists. Please go back and choose a different name.",

"notification_name_exists" =>
"A notification by the name '%name%' already exists. Please go back and choose a different name.",

"form_name_exists" =>
"A form by the name '%name%' already exists. Please go back and choose a different name.",

"freeform_reserved_field_name" =>
"The field name, '%name%' is a word reserved by Freeform. Please go back and choose a different name.",

"reserved_form_name" =>
"The form name, '%name%' is a word reserved by Freeform. Please go back and choose a different name.",

"reserved_notification_name" =>
"The notification name, '%name%' is a word reserved by Freeform. Please go back and choose a different name.",

"reserved_template_name" =>
"The template name, '%name%' is a word reserved by Freeform. Please go back and choose a different name.",

'from_email_required' =>
"The from email is required for notifications",

'from_name_required' =>
"The from name is required for notifications",

'email_subject_required' =>
"The email subject is required for notifications",

"no_duplicates" =>
"No duplicate postings are allowed for this form.",

'required_field_missing' =>
"Required field missing input",

'fields_do_not_match' =>
"The following required matching fields do not match: ",

'generic_invalid_field_input' =>
"Invalid input.",

'not_a_number' =>
"Not a number",

'not_an_integer' =>
"Not an integer",

'not_a_decimal' =>
"Not a decimal",

'not_valid_email' =>
"Not a valid email",

'number_exceeds_limit' =>
"Number exceeds allowed maximum",

'max_length_exceeded' =>
"Maximum field length of %num% exceeded.",

'field_settings_error' =>
"Options for this field type had errors.",

//this is for concatenating to a standard field with a required match
'confirm' =>
"Confirm",

//general errors
'invalid_request' =>
"Invalid Request",

'freeform_module_disabled' =>
"The Freeform module is currently disabled.  Please insure it is installed and up to date by going
to the module's control panel in the ExpressionEngine Control Panel",

'disable_module_to_disable_extension' =>
"To disable this extension, you must disable its corresponding <a href='%url%'>module</a>.",

'enable_module_to_enable_extension' =>
"To enable this extension, you must install its corresponding <a href='%url%'>module</a>.",

//field multi row save error
'you_must_choose_field_options' =>
"You must choose some data option for multi-rows in order for this field type to work.",

'invalid_country' =>
"Invalid Country",

'invalid_province' =>
"Invalid Province",

'invalid_state' =>
"Invalid State",

'invalid_fieldtype' =>
"Invalid field type",

'error_creating_export' =>
"Error creating export",

'author_edit_only' =>
"Only the author of this entry may edit it.",

// -------------------------------------
//	file upload errors
// -------------------------------------

'upload_userfile_not_set' =>
"Unable to find a post variable called user file.",

'upload_file_exceeds_limit' =>
"The uploaded file exceeds the maximum allowed size in your PHP configuration file.",

'upload_file_exceeds_form_limit' =>
"The uploaded file exceeds the maximum size allowed by the submission form.",

'upload_file_partial' =>
"The file was only partially uploaded.",

'upload_no_temp_directory' =>
"The temporary folder is missing.",

'upload_unable_to_write_file' =>
"The file could not be written to disk.",

'upload_stopped_by_extension' =>
"The file upload was stopped by extension.",

'upload_no_file_selected' =>
"You did not select a file to upload.",

'upload_invalid_filetype' =>
"The filetype you are attempting to upload is not allowed.",

'upload_invalid_filesize' =>
"The file you are attempting to upload is larger than the permitted size.",

'upload_invalid_dimensions' =>
"The image you are attempting to upload exceeds the maximum height or width.",

'upload_destination_error' =>
"A problem was encountered while attempting to move the uploaded file to the final destination.",

'upload_no_filepath' =>
"The upload path does not appear to be valid.",

'upload_no_file_types' =>
"You have not specified any allowed file types.",

'upload_bad_filename' =>
"The file name you submitted already exists on the server.",

'upload_not_writable' =>
"The upload destination folder does not appear to be writable.",

//----------------------------------------
//  Update routine
//----------------------------------------

'update_freeform_module' =>
"Update Freeform Module",

'freeform_update_message' =>
"You have recently uploaded a new version of Freeform, please click here to run the update script.",

"update_successful" =>
"The module was successfully updated.",

"update_failure" =>
"There was an error while trying to update your module to the latest version.",

// -------------------------------------
//	State, Province and Country list last because
// 	its stupid long
// -------------------------------------

'list_of_us_states' => "
Alabama (AL)
Alaska (AK)
Arizona (AZ)
Arkansas (AR)
California (CA)
Colorado (CO)
Connecticut (CT)
Delaware (DE)
District of Columbia (DC)
Florida (FL)
Georgia (GA)
Guam (GU)
Hawaii (HI)
Idaho (ID)
Illinois (IL)
Indiana (IN)
Iowa (IA)
Kansas (KS)
Kentucky (KY)
Louisiana (LA)
Maine (ME)
Maryland (MD)
Massachusetts (MA)
Michigan (MI)
Minnesota (MN)
Mississippi (MS)
Missouri (MO)
Montana (MT)
Nebraska (NE)
Nevada (NV)
New Hampshire (NH)
New Jersey (NJ)
New Mexico (NM)
New York (NY)
North Carolina (NC)
North Dakota (ND)
Ohio (OH)
Oklahoma (OK)
Oregon (OR)
Pennsylvania (PA)
Puerto Rico (PR)
Rhode Island (RI)
South Carolina (SC)
South Dakota (SD)
Tennessee (TN)
Texas (TX)
Utah (UT)
Vermont (VT)
Virginia (VA)
Virgin Islands (VI)
Washington (WA)
West Virginia (WV)
Wisconsin (WI)
Wyoming (WY)",

'list_of_canadian_provinces' => "
Alberta (AB)
British Columbia (BC)
Manitoba (MB)
New Brunswick (NB)
Newfoundland and Labrador (NL)
Northwest Territories (NT)
Nova Scotia (NS)
Nunavut (NU)
Ontario (ON)
Prince Edward Island (PE)
Quebec (QC)
Saskatchewan (SK)
Yukon (YT)",

'list_of_countries' => "
Afghanistan
Albania
Algeria
Andorra
Angola
Antigua & Deps
Argentina
Armenia
Australia
Austria
Azerbaijan
Bahamas
Bahrain
Bangladesh
Barbados
Belarus
Belgium
Belize
Benin
Bhutan
Bolivia
Bosnia Herzegovina
Botswana
Brazil
Brunei
Bulgaria
Burkina
Burundi
Cambodia
Cameroon
Canada
Cape Verde
Central African Rep
Chad
Chile
China
Colombia
Comoros
Congo
Congo {Democratic Rep}
Costa Rica
Croatia
Cuba
Cyprus
Czech Republic
Denmark
Djibouti
Dominica
Dominican Republic
East Timor
Ecuador
Egypt
El Salvador
Equatorial Guinea
Eritrea
Estonia
Ethiopia
Fiji
Finland
France
Gabon
Gambia
Georgia
Germany
Ghana
Greece
Grenada
Guatemala
Guinea
Guinea-Bissau
Guyana
Haiti
Honduras
Hungary
Iceland
India
Indonesia
Iran
Iraq
Ireland {Republic}
Israel
Italy
Ivory Coast
Jamaica
Japan
Jordan
Kazakhstan
Kenya
Kiribati
Korea North
Korea South
Kosovo
Kuwait
Kyrgyzstan
Laos
Latvia
Lebanon
Lesotho
Liberia
Libya
Liechtenstein
Lithuania
Luxembourg
Macedonia
Madagascar
Malawi
Malaysia
Maldives
Mali
Malta
Marshall Islands
Mauritania
Mauritius
Mexico
Micronesia
Moldova
Monaco
Mongolia
Montenegro
Morocco
Mozambique
Myanmar, {Burma}
Namibia
Nauru
Nepal
Netherlands
New Zealand
Nicaragua
Niger
Nigeria
Norway
Oman
Pakistan
Palau
Panama
Papua New Guinea
Paraguay
Peru
Philippines
Poland
Portugal
Qatar
Romania
Russian Federation
Rwanda
St Kitts & Nevis
St Lucia
Saint Vincent & the Grenadines
Samoa
San Marino
Sao Tome & Principe
Saudi Arabia
Senegal
Serbia
Seychelles
Sierra Leone
Singapore
Slovakia
Slovenia
Solomon Islands
Somalia
South Africa
South Sudan
Spain
Sri Lanka
Sudan
Suriname
Swaziland
Sweden
Switzerland
Syria
Taiwan
Tajikistan
Tanzania
Thailand
Togo
Tonga
Trinidad & Tobago
Tunisia
Turkey
Turkmenistan
Tuvalu
Uganda
Ukraine
United Arab Emirates
United Kingdom
United States
Uruguay
Uzbekistan
Vanuatu
Vatican City
Venezuela
Vietnam
Yemen
Zambia
Zimbabwe",

// END
''=>''
);
