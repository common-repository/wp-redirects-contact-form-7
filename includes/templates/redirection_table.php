<?php

// WP_List_Table is not loaded automatically so we need to load it in our application
if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class CF7_Redirection_Table extends WP_List_Table
{
	function get_columns()
	{
		$columns = array(
			'title' => 'Title',
			'shortcode'    => 'Shortcode',
			'redirection-page'    => 'Redirection Page/URL',
			'action'    => 'Action',
		);
		return $columns;
	}

	function prepare_items()
	{
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->yspl_fill_table_data();
	}

	function yspl_fill_table_data()
	{

		$data = array();
		$formData = get_posts(array("post_type" => "wpcf7_contact_form"));
		if (!empty($formData)) {
			foreach ($formData as $key => $form) {
				$data[$key]['shortcode'] = ("[contact-form-7 id=" . $form->ID . " title=" . $form->post_title . "]");
				$data[$key]['title'] = $form->post_title;

				$check_id = get_post_meta($form->ID, 'check_id', true);
				if ($check_id == 1) {
					$page = get_the_title(get_post_meta($form->ID, 'succ_page_id', true));
					if (empty($page) || $page === 'Untitled')
						$page = "--";
				} elseif ($check_id == 2) {
					$page = get_post_meta($form->ID, 'succ_page_url', true);
					if (empty($page) || $page == 'Untitled')
						$page = "--";
				}
				$data[$key]['redirection-page'] = $page;
				$tab = get_option('cf7r_tab', true);
				$data[$key]['action'] = ('<a href="' . get_admin_url() . 'admin.php?page=wpcf7&post=' . $form->ID . '&active-tab=' . $tab . '">Edit</a>');
			}
		}

		return $data;
	}

	public function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'id':
			case 'title':
			case 'shortcode':
			case 'redirection-page':
			case 'action':
				return $item[$column_name];
			default:
				return print_r($item, true);
		}
	}
}

// $cf7rTable = new CF7_Redirection_Table();

// Render your page outside the class
function yspl_cf7r_render_redirection_table_page()
{
	global $wpdb;

	$Obj_Wp_Ban_User = new CF7_Redirection_Table();
	$Obj_Wp_Ban_User->prepare_items();
}
