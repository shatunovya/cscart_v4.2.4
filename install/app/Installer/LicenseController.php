<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Installer;

class LicenseController
{
    /**
     * License index action
     *
     * @param  string $license_type
     * @param  string $license_number
     * @param  string $license_agreement
     * @return bool   lways true
     */
    public function actionIndex($license_agreement)
    {
        return true;
    }

    /**
     * License next_step action
     *
     * @param  string $license_type
     * @param  string $license_number
     * @param  string $license_agreement
     * @return bool   true if step fully completed
     */
    public function actionNextStep($license_agreement)
    {
        $app = App::instance();
        $step_is_valid = true;

        if (empty($license_agreement)) {
            $app->setNotification('E', $app->t('error'), $app->t('you_need_to_accept_agreements'), true);
            $step_is_valid = false;

        }

        $params = array(
            'license_agreement' => $license_agreement,
        );

        if ($step_is_valid) {
            $app->setToStorage('license_agreement', $license_agreement);

            $params['dispatch'] = 'setup';
            $app->run($params);

        } else {
            $params['dispatch'] = 'license';
            $app->run($params);
        }

        return false;
    }
}
