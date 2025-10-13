<?php

namespace Modules\Notification\Actions;

use Modules\Notification\Notification_Model;

class CreateNewNotification
{
    private array $submitted_data;
    private ?Notification_Model $model;

    public function __construct(array $submitted_data, ?Notification_Model $model = null)
    {
        $this->submitted_data = $submitted_data;
        $this->model = $model ?? new Notification_Model();
    }

    public function init(): array
    {
        // prepare data to save
        $data = [
            'opt_mx_notification_type_id' => filter_var($this->submitted_data['opt_mx_notification_type_id'], FILTER_SANITIZE_NUMBER_INT),
            'tar_message' => $this->submitted_data['tar_message'],
            'dat_from_date' => $this->submitted_data['dat_from_date'],
            'dat_to_date' => $this->submitted_data['dat_to_date'],
            'dat_added_date' => date('Y-m-d H:i:s'),
            'opt_mx_state_id' => 1,
            'int_added_by' => filter_var($_SESSION['id'], FILTER_SANITIZE_NUMBER_INT),
        ];

        if ($this->submitted_data['opt_mx_notification_type_id'] == 2) {
            $data['opt_mx_application_id'] = $this->submitted_data['opt_mx_application_id'];
        }
        $saveNotification = $this->model->db->save($this->model->getTable(), $data, 'NOTIFICATION_MODEL');
        if (!$saveNotification) {
            return ['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to save notification.'];
        }
        return ['status' => true, 'code' => 201, 'message' => 'Notification added successfully'];
    }
}