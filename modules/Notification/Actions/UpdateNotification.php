<?php

namespace Modules\Notification\Actions;

use Modules\Notification\Notification_Model;

class UpdateNotification
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
        $data = [
            'opt_mx_notification_type_id' => $this->submitted_data['opt_mx_notification_type_id'],
            'tar_message' => $this->submitted_data['tar_message'],
            'dat_from_date' => $this->submitted_data['dat_from_date'],
            'dat_to_date' => $this->submitted_data['dat_to_date']
        ];

        if ($this->submitted_data['opt_mx_notification_type_id'] == 2) {
            $data['opt_mx_application_id'] = $this->submitted_data['opt_mx_application_id'];
        }
        $updateNotification = $this->model->db->update($this->model->getTable(), $data, $this->submitted_data['id']);
        if (!$updateNotification) {
            return ['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to update notification.'];
        }
        return ['status' => true, 'code' => 201, 'message' => 'Notification update successfully'];
    }

}