<?php
require_once('abstract.php');
/*
 * @author Stoyvo
 */
class Pools_Litecoinpool extends Pools_Abstract {

    // Pool Information
    protected $_apiKey;
    protected $_type = 'litecoinpool';

    public function __construct($params) {
        parent::__construct(array('apiurl' => 'https://www.litecoinpool.org'));
        $this->_apiKey = $params['apikey'];
        $this->_fileHandler = new FileHandler('pools/' . $this->_type . '/'. hash('md4', $params['apikey']) .'.json');
    }

    public function update() {
        if ($GLOBALS['cached'] == false || $this->_fileHandler->lastTimeModified() >= 30) { // updates every 30 seconds

            $poolData = curlCall($this->_apiURL  . '/api?api_key='. $this->_apiKey);
			

            // Offline Check
            if (empty($poolData)) {
                return;
            }

            // Data Order
            $data['type'] = $this->_type;

            // Pool Speed
            
            $data['user_hashrate'] = 0;
			$data['LTC_per_day'] = number_format(((501656.9049536115 * $poolData['pool']['pps_ratio']) / $poolData['network']['difficulty']) / 1000 * 63.85,4);
			$data['USD_per_day'] = number_format($data['LTC_per_day'] * $poolData['market']['ltc_usd'] ,2) . ' USD';
            $data['paid_LTC'] = $poolData['user']['paid_rewards'];
            $data['unpaid_LTC'] = $poolData['user']['unpaid_rewards'];
            $data['past_24h_LTC'] = $poolData['user']['past_24h_rewards'];
            $data['LTC_difficulty'] = $poolData['network']['difficulty'];
			
            $data['valid_LTC_shares'] = 0;
            $data['stale_LTC_shares'] = 0;
            $data['duplicate_LTC_shares'] = 0;
            $data['unknown_LTC_shares'] = 0;

            foreach ($poolData['workers'] as $worker) {
                $data['user_hashrate'] += $worker['hash_rate'];

                $data['valid_LTC_shares'] += $worker['valid_shares'];
                $data['stale_LTC_shares'] += $worker['stale_shares'];
                $data['invalid_LTC_shares'] += $worker['invalid_shares'];

            }

            $data['active_workers'] = 0;
            if (!empty($poolData['workers'])) {
                foreach($poolData['workers'] as $worker) {
                    if ($worker['hash_rate'] != 0) {
                        $data['active_workers']++;
                    }

                }
            }

            // Clear data if it's missing
            foreach ($data as $key => $value) {
                if ($value == 0) {
                    unset($data[$key]);
                }
            }
			$data['pool_hashrate'] = formatHashRate($poolData['pool']['hash_rate']);
            $data['url'] = $this->_apiURL;

            $this->_fileHandler->write(json_encode($data));
            return $data;
        }

        return json_decode($this->_fileHandler->read(), true);
    }

}
