<?php
class ControllerCheckoutGuestShipping extends Controller {
	public function index() {
		$this->load->language('checkout/checkout');

		if (isset($this->session->data['shipping_address']['firstname'])) {
			$data['firstname'] = $this->session->data['shipping_address']['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->session->data['shipping_address']['lastname'])) {
			$data['lastname'] = $this->session->data['shipping_address']['lastname'];
		} else {
			$data['lastname'] = '';
		}

		if (isset($this->session->data['shipping_address']['fullname'])) {
			$data['fullname'] = $this->session->data['shipping_address']['fullname'];
		} else {
			$data['fullname'] = '';
		}

		if (isset($this->session->data['shipping_address']['telephone'])) {
			$data['telephone'] = $this->session->data['shipping_address']['telephone'];
		} else {
			$data['telephone'] = '';
		}

		if (isset($this->session->data['shipping_address']['company'])) {
			$data['company'] = $this->session->data['shipping_address']['company'];
		} else {
			$data['company'] = '';
		}

		if (isset($this->session->data['shipping_address']['address_1'])) {
			$data['address_1'] = $this->session->data['shipping_address']['address_1'];
		} else {
			$data['address_1'] = '';
		}

		if (isset($this->session->data['shipping_address']['address_2'])) {
			$data['address_2'] = $this->session->data['shipping_address']['address_2'];
		} else {
			$data['address_2'] = '';
		}

		if (isset($this->session->data['shipping_address']['postcode'])) {
			$data['postcode'] = $this->session->data['shipping_address']['postcode'];
		} else {
			$data['postcode'] = '';
		}

		if (isset($this->session->data['shipping_address']['city'])) {
			$data['city'] = $this->session->data['shipping_address']['city'];
		} else {
			$data['city'] = '';
		}

		if (isset($this->session->data['shipping_address']['country_id'])) {
			$data['country_id'] = $this->session->data['shipping_address']['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->session->data['shipping_address']['zone_id'])) {
			$data['zone_id'] = $this->session->data['shipping_address']['zone_id'];
		} else {
			$data['zone_id'] = '';
		}

		if (isset($this->session->data['shipping_address']['city_id'])) {
			$data['city_id'] = $this->session->data['shipping_address']['city_id'];
		} else {
			$data['city_id'] = '';
		}

		if (isset($this->session->data['shipping_address']['district_id'])) {
			$data['district_id'] = $this->session->data['shipping_address']['district_id'];
		} else {
			$data['district_id'] = '';
		}

		if ($this->config->get('config_sales_country_id')) {
			$data['sales_country_id'] = $this->config->get('config_sales_country_id');

			$data['country_id'] = $data['sales_country_id'];
		}

		if ($this->config->get('config_sales_zone_id')) {
			$data['sales_zone_id'] = $this->config->get('config_sales_zone_id');

			$data['zone_id'] = $data['sales_zone_id'];
		}

		if ($this->config->get('config_sales_city_id')) {
			$data['sales_city_id'] = $this->config->get('config_sales_city_id');

			$data['city_id'] = $data['sales_city_id'];
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$this->load->model('localisation/zone');

		if (!empty($data['country_id'])) {
			$data['zones'] = $this->model_localisation_zone->getZonesByCountryId($data['country_id']);
		}

		if (!empty($data['zone_id'])) {
			$data['cities'] = $this->model_localisation_zone->getZonesByParentId($data['zone_id']);
		} elseif (isset($data['zones']) && !empty($data['zones'])) {
			$data['cities'] = $this->model_localisation_zone->getZonesByParentId($data['zones'][0]['zone_id']);
		}

		if (!empty($data['city_id'])) {
			$data['districts'] = $this->model_localisation_zone->getZonesByParentId($data['city_id']);
		} elseif (isset($data['cities']) && !empty($data['cities'])) {
			$data['districts'] = $this->model_localisation_zone->getZonesByParentId($data['cities'][0]['zone_id']);
		}

		// Custom Fields
		$this->load->model('account/custom_field');
		
		$custom_fields = $this->model_account_custom_field->getCustomFields($this->session->data['guest']['customer_group_id']);

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'address') {
				$data['custom_fields'][] = $custom_field;
			}
		}
		
		if (isset($this->session->data['shipping_address']['custom_field'])) {
			$data['address_custom_field'] = $this->session->data['shipping_address']['custom_field'];
		} else {
			$data['address_custom_field'] = array();
		}
		
		$this->response->setOutput($this->load->view('checkout/guest_shipping', $data));
	}

	public function save() {
		$this->load->language('checkout/checkout');

		$json = array();

		// Validate if customer is logged in.
		if ($this->customer->isLogged()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$json['redirect'] = $this->url->link('checkout/cart');
		}

		// Check if guest checkout is available.
		if (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price') || $this->cart->hasDownload()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		if (!$json) {
/*			if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}

			if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}*/

			if ((utf8_strlen(trim($this->request->post['fullname'])) < 1) || (utf8_strlen(trim($this->request->post['fullname'])) > 32)) {
				$json['error']['fullname'] = $this->language->get('error_fullname');
			}

			if ((utf8_strlen(trim($this->request->post['telephone'])) < 1) || (utf8_strlen(trim($this->request->post['telephone'])) > 32)) {
				$json['error']['telephone'] = $this->language->get('error_telephone');
			}

			if ((utf8_strlen(trim($this->request->post['address_1'])) < 1) || (utf8_strlen(trim($this->request->post['address_1'])) > 128)) {
				$json['error']['address_1'] = $this->language->get('error_address_1');
			}

/*			if ((utf8_strlen(trim($this->request->post['city'])) < 2) || (utf8_strlen(trim($this->request->post['city'])) > 128)) {
				$json['error']['city'] = $this->language->get('error_city');
			}
*/
			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

			if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($this->request->post['postcode'])) < 2 || utf8_strlen(trim($this->request->post['postcode'])) > 10)) {
				$json['error']['postcode'] = $this->language->get('error_postcode');
			}

			if (!isset($this->request->post['country_id']) || !$this->request->post['country_id'] ||!is_numeric($this->request->post['country_id'])) {
				$json['error']['country'] = $this->language->get('error_country');
			}

			if (!isset($this->request->post['zone_id']) || !$this->request->post['zone_id'] || !is_numeric($this->request->post['zone_id'])) {
				$this->error['zone'] = $this->language->get('error_zone');
			}

			$this->load->model('localisation/zone');

			if (isset($this->request->post['zone_id']) && $this->request->post['zone_id'] > 0) {
				$cities = $this->model_localisation_zone->getZonesByParentId($this->request->post['zone_id']);

				if ($cities && (!isset($this->request->post['city_id']) || !$this->request->post['city_id'] || !is_numeric($this->request->post['city_id']))) {
					$this->error['city'] = $this->language->get('error_city');
				}
			}

			if (isset($this->request->post['city_id']) && $this->request->post['city_id'] > 0) {
				$districts = $this->model_localisation_zone->getZonesByParentId($this->request->post['city_id']);

				if ($districts && (!isset($this->request->post['district_id']) || !$this->request->post['district_id'] || !is_numeric($this->request->post['district_id']))) {
					$this->error['district'] = $this->language->get('error_district');
				}
			}

			// Custom field validation
			$this->load->model('account/custom_field');

			$custom_fields = $this->model_account_custom_field->getCustomFields($this->session->data['guest']['customer_group_id']);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address') { 
					if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
						$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
						$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					}
				}
			}
		}

		if (!$json) {
			//$this->session->data['shipping_address']['firstname'] = $this->request->post['firstname'];
			//$this->session->data['shipping_address']['lastname'] = $this->request->post['lastname'];
			$this->session->data['shipping_address']['fullname'] = $this->request->post['fullname'];
			$this->session->data['shipping_address']['telephone'] = $this->request->post['telephone'];
			//$this->session->data['shipping_address']['company'] = $this->request->post['company'];
			$this->session->data['shipping_address']['address_1'] = $this->request->post['address_1'];
			//$this->session->data['shipping_address']['address_2'] = $this->request->post['address_2'];
			$this->session->data['shipping_address']['postcode'] = $this->request->post['postcode'];
			//$this->session->data['shipping_address']['city'] = $this->request->post['city'];
			$this->session->data['shipping_address']['country_id'] = $this->request->post['country_id'];
			$this->session->data['shipping_address']['zone_id'] = $this->request->post['zone_id'];
			$this->session->data['shipping_address']['city_id'] = $this->request->post['city_id'];
			$this->session->data['shipping_address']['district_id'] = $this->request->post['district_id'];

			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

			if ($country_info) {
				$this->session->data['shipping_address']['country'] = $country_info['name'];
				$this->session->data['shipping_address']['iso_code_2'] = $country_info['iso_code_2'];
				$this->session->data['shipping_address']['iso_code_3'] = $country_info['iso_code_3'];
				$this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
			} else {
				$this->session->data['shipping_address']['country'] = '';
				$this->session->data['shipping_address']['iso_code_2'] = '';
				$this->session->data['shipping_address']['iso_code_3'] = '';
				$this->session->data['shipping_address']['address_format'] = '';
			}

			$this->load->model('localisation/zone');

			$zone_info = $this->model_localisation_zone->getZone($this->request->post['zone_id']);

			if ($zone_info) {
				$this->session->data['shipping_address']['zone'] = $zone_info['name'];
				$this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
			} else {
				$this->session->data['shipping_address']['zone'] = '';
				$this->session->data['shipping_address']['zone_code'] = '';
			}

			$city_info = $this->model_localisation_zone->getZone($this->request->post['city_id']);

			if ($city_info) {
				$this->session->data['shipping_address']['city'] = $city_info['name'];
			} else {
				$this->session->data['shipping_address']['city'] = '';
			}

			$district_info = $this->model_localisation_zone->getZone($this->request->post['district_id']);

			if ($district_info) {
				$this->session->data['shipping_address']['district'] = $district_info['name'];
			} else {
				$this->session->data['shipping_address']['district'] = '';
			}

			if (isset($this->request->post['custom_field'])) {
				$this->session->data['shipping_address']['custom_field'] = $this->request->post['custom_field']['address'];
			} else {
				$this->session->data['shipping_address']['custom_field'] = array();
			}

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}