CREATE TABLE IF NOT EXISTS `exp_freeform_forms` (
	`form_id`						int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	`form_name`						varchar(150)		NOT NULL DEFAULT 'default',
	`form_label`					varchar(150)		NOT NULL DEFAULT 'default',
	`default_status`				varchar(150)		NOT NULL DEFAULT 'default',
	`notify_user`					char(1)				NOT NULL DEFAULT 'n',
	`notify_admin`					char(1)				NOT NULL DEFAULT 'n',
	`user_email_field`				varchar(150)		NOT NULL DEFAULT '',
	`user_notification_id`			int(10) unsigned	NOT NULL DEFAULT 0,
	`admin_notification_id`			int(10) unsigned	NOT NULL DEFAULT 0,
	`admin_notification_email`		text,
	`form_description`				text,
	`field_ids`						text,
	`field_order`					text,
	`template_id`					int(10) unsigned	NOT NULL DEFAULT 0,
	`composer_id`					int(10) unsigned	NOT NULL DEFAULT 0,
	`author_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`entry_date`					int(10) unsigned	NOT NULL DEFAULT 0,
	`edit_date`						int(10) unsigned	NOT NULL DEFAULT 0,
	`settings`						text,
	PRIMARY KEY						(`form_id`),
	KEY								(`form_name`),
	KEY								(`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_composer_layouts` (
	`composer_id`					int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`composer_data`					text,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	`preview`						char(1)				NOT NULL DEFAULT 'n',
	`entry_date`					int(10) unsigned	NOT NULL DEFAULT 0,
	`edit_date`						int(10) unsigned	NOT NULL DEFAULT 0,
	PRIMARY KEY						(`composer_id`),
	KEY								(`preview`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_fields` (
	`field_id`						int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	`field_name`					varchar(150)		NOT NULL DEFAULT 'default',
	`field_label`					varchar(150)		NOT NULL DEFAULT 'default',
	`field_type`					varchar(50)			NOT NULL DEFAULT 'text',
	`settings`						text,
	`author_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`entry_date`					int(10) unsigned	NOT NULL DEFAULT 0,
	`edit_date`						int(10) unsigned	NOT NULL DEFAULT 0,
	`required`						char(1)				NOT NULL DEFAULT 'n',
	`submissions_page`				char(1)				NOT NULL DEFAULT 'y',
	`moderation_page`				char(1)				NOT NULL DEFAULT 'y',
	`composer_use`					char(1)				NOT NULL DEFAULT 'y',
	`field_description`				text,
	PRIMARY KEY						(`field_id`),
	KEY								(`field_name`),
	KEY								(`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_fieldtypes` (
	`fieldtype_id`					int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`fieldtype_name`				varchar(250),
	`settings`						text,
	`default_field`					char(1)				NOT NULL DEFAULT 'n',
	`version`						varchar(12),
	PRIMARY KEY						(`fieldtype_id`),
	KEY								(`fieldtype_name`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_composer_templates` (
	`template_id`					int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	`template_name`					varchar(150)		NOT NULL DEFAULT 'default',
	`template_label`				varchar(150)		NOT NULL DEFAULT 'default',
	`template_description`			text,
	`enable_template`				char(1)				NOT NULL DEFAULT 'y',
	`template_data`					text,
	`param_data`					text,
	PRIMARY KEY						(`template_id`),
	KEY								(`template_name`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_notification_templates` (
	`notification_id`				int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	`notification_name`				varchar(150)		NOT NULL DEFAULT 'default',
	`notification_label`			varchar(150)		NOT NULL DEFAULT 'default',
	`notification_description`		text,
	`wordwrap`						char(1)				NOT NULL DEFAULT 'y',
	`allow_html`					char(1)				NOT NULL DEFAULT 'n',
	`from_name`						varchar(150)		NOT NULL DEFAULT '',
	`from_email`					varchar(250)		NOT NULL DEFAULT '',
	`reply_to_email`				varchar(250)		NOT NULL DEFAULT '',
	`email_subject`					varchar(128)		NOT NULL DEFAULT 'default',
	`include_attachments`			char(1)				NOT NULL DEFAULT 'n',
	`template_data`					text,
	PRIMARY KEY						(`notification_id`),
	KEY								(`notification_name`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_params` (
	`params_id`						int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`entry_date`					int(10) unsigned	NOT NULL DEFAULT 0,
	`data`							text,
	PRIMARY KEY						(`params_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_preferences` (
	`preference_id`					int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`preference_name`				varchar(80),
	`preference_value`				text,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	PRIMARY KEY						(`preference_id`),
	KEY								(`preference_name`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_user_email` (
	`email_id`						int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	`author_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`ip_address`					varchar(40)			NOT NULL DEFAULT 0,
	`entry_date`					int(10) unsigned	NOT NULL DEFAULT 0,
	`form_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`entry_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`email_count`					int(10) unsigned	NOT NULL DEFAULT 0,
	`email_addresses`				text,
	PRIMARY KEY						(`email_id`),
	KEY								(`ip_address`)
) CHARACTER SET utf8 COLLATE utf8_general_ci;;

CREATE TABLE IF NOT EXISTS `exp_freeform_file_uploads` (
	`file_id`						int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	`form_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`entry_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`field_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`server_path`					varchar(750),
	`filename`						varchar(250),
	`extension`						varchar(20),
	`filesize`						int(10)				NOT NULL DEFAULT 0,
	PRIMARY KEY						(`file_id`),
	KEY								(`entry_id`),
	KEY								(`field_id`),
	KEY								(`extension`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_freeform_multipage_hashes` (
	`hash_id`						int(10) unsigned	NOT NULL AUTO_INCREMENT,
	`site_id`						int(10) unsigned	NOT NULL DEFAULT 1,
	`form_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`entry_id`						int(10) unsigned	NOT NULL DEFAULT 0,
	`hash`							varchar(32)			NOT NULL DEFAULT '',
	`ip_address`					varchar(40)			NOT NULL DEFAULT 0,
	`date`							int(10) unsigned	NOT NULL DEFAULT 0,
	`edit`							char(1)				NOT NULL DEFAULT 'n',
	`data`							text,
	PRIMARY KEY						(`hash_id`),
	KEY								(`hash`),
	KEY								(`ip_address`),
	KEY								(`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci;;
