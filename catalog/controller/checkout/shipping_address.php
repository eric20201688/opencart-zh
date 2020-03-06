<?php
class ControllerCheckoutShippingAddress extends Controller {
	public function index() {
		$this->load->language('checkout/checkout');

		if (isset($this->session->data['shipping_address']['address_id'])) {
			$data['address_id'] = $this->session->data['shipping_address']['address_id'];
		} else {
			$data['address_id'] = $this->customer->getAddressId();
		}

		$this->load->model('account/address');

		$data['addresses'] = $this->model_account_address->getAddresses();

		if (isset($this->session->data['shipping_address']['postcode'])) {
			$data['postcode'] = $this->session->data['shipping_address']['postcode'];
		} else {
			$data['postcode'] = '';
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

		if (isset($this->session->data['payment_address']['city_id'])) {
			$data['city_id'] = $this->session->data['payment_address']['city_id'];
		} else {
			$data['city_id'] = '';
		}

		if (isset($this->session->data['payment_address']['district_id'])) {
			$data['district_id'] = $this->session->data['payment_address']['district_id'];
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

		if ($data['country_id']) {
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
		$data['custom_fields'] = array();
		
		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'address') {
				$data['custom_fields'][] = $custom_field;
			}
		}

		if (isset($this->session->data['shipping_address']['custom_field'])) {
			$data['shipping_address_custom_field'] = $this->session->data['shipping_address']['custom_field'];
		} else {
			$data['shipping_address_custom_field'] = array();
		}
		
		$this->response->setOutput($this->load->view('checkout/shipping_address', $data));
	}

	public function save() {
		$this->load->language('checkout/checkout');
		
		$json = array();

		// Validate if customer is logged in.
		if (!$this->customer->isLogged()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		// Validate if shipping is required. If not the customer should not have reached this page.
		if (!$this->cart->hasShipping()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$json['redirect'] = $this->url->link('checkout/cart');
		}

		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$json['redirect'] = $this->url->link('checkout/cart');

				break;
			}
		}

		if (!$json) {
			$this->load->model('account/address');
			
			if (isset($this->request->post['shipping_address']) && $this->request->post['shipping_address'] == 'existing') {
				if (empty($this->request->post['address_id'])) {
					$json['error']['warning'] = $this->language->get('error_address');
				} elseif (!in_array($this->request->post['address_id'], array_keys($this->model_account_address->getAddresses()))) {
					$json['error']['warning'] = $this->language->get('error_address');
				}

				if (!$json) {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->request->post['address_id']);

					unset($this->session->data['shipping_method']);
					unset($this->session->data['shipping_methods']);
				}
			} else {
				/*if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
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

				/*if ((utf8_strlen(trim($this->request->post['city'])) < 2) || (utf8_strlen(trim($this->request->post['city'])) > 128)) {
					$json['error']['city'] = $this->language->get('error_city');
				}*/

				$this->load->model('localisation/country');

				$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

				if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($this->request->post['postcode'])) < 2 || utf8_strlen(trim($this->request->post['postcode'])) > 10)) {
					$json['error']['postcode'] = $this->language->get('error_postcode');
				}

				if (!isset($this->request->post['country_id']) || !$this->request->post['country_id'] || !is_numeric($this->request->post['country_id'])) {
					$json['error']['country'] = $this->language->get('error_country');
				}

				$this->load->model('localisation/zone');

				if (isset($this->request->post['country_id']) && $this->request->post['country_id'] > 0) {
					$zones = $this->model_localisation_zone->getZonesByCountryId($this->request->post['country_id']);

					if ($zones && (!isset($this->request->post['zone_id']) || !$this->request->post['zone_id'] || !is_numeric($this->request->post['zone_id']))) {
						$json['error']['zone'] = $this->language->get('error_zone');
					}
				}

				if (isset($this->request->post['zone_id']) && $this->request->post['zone_id'] > 0) {
					$cities = $this->model_localisation_zone->getZonesByParentId($this->request->post['zone_id']);

					if ($cities && (!isset($this->request->post['city_id']) || !$this->request->post['city_id'] || !is_numeric($this->request->post['city_id']))) {
						$json['error']['city'] = $this->language->get('error_city');
					}
				}

				if (isset($this->request->post['city_id']) && $this->request->post['city_id'] > 0) {
					$districts = $this->model_localisation_zone->getZonesByParentId($this->request->post['city_id']);

					if ($districts && (!isset($this->request->post['district_id']) || !$this->request->post['district_id'] || !is_numeric($this->request->post['district_id']))) {
						$json['error']['district'] = $this->language->get('error_district');
					}
				}

				// Custom field validation
				$this->load->model('account/custom_field');

				$custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

				foreach ($custom_fields as $custom_field) {
					if ($custom_field['location'] == 'address') {
						if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
							$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
						} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
							$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
						}
					}
				}

				if (!$json) {
					$address_id = $this->model_account_address->addAddress($this->customer->getId(), $this->request->post);

					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($address_id);

					// If no default address ID set we use the last address
					if (!$this->customer->getAddressId()) {
						$this->load->model('account/customer');
						
						$this->model_account_customer->editAddressId($this->customer->getId(), $address_id);
					}
					
					unset($this->session->data['shipping_method']);
					unset($this->session->data['shipping_methods']);
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}