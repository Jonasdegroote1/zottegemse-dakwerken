<?php

namespace Drupal\inventory_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

class VehicleController extends ControllerBase {

  /**
  * The messenger service.
  *
  * @var \Drupal\Core\Messenger\MessengerInterface
  */
  protected $messenger;

  /**
  * Constructs a new VehicleController object.
  *
  * @param \Drupal\Core\Messenger\MessengerInterface $messenger
  *   The messenger service.
  */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
  * {@inheritdoc}
  */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * List all vehicles.
   */
  public function listItems() {
    $connection = Database::getConnection();
    $query = $connection->select('vehicles', 'v')
      ->fields('v', ['name', 'vehicle_id'])
      ->orderBy('name');
    $result = $query->execute()->fetchAll();

    // Prepare table header.
    $header = [
      'vehicle_name' => $this->t('Vehicle Name'),
      'operations' => $this->t('Operations'),
    ];

    // Prepare table rows.
    $rows = [];
    foreach ($result as $record) {
      $edit_url = Url::fromRoute('inventory_system.vehicle_edit_form', ['vid' => $record->vehicle_id]);
      $edit_link = Link::fromTextAndUrl($this->t('Edit'), $edit_url);
      $delete_url = Url::fromRoute('inventory_system.vehicle_delete', ['vid' => $record->vehicle_id]);
      $delete_link = Link::fromTextAndUrl($this->t('Delete'), $delete_url);

      $rows[] = [
        'vehicle_name' => $record->name,
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $edit_link->getText(),
                'url' => $edit_link->getUrl(),
              ],
              'delete' => [
                'title' => $delete_link->getText(),
                'url' => $delete_link->getUrl(),
              ],
            ],
          ],
        ],
      ];
    }

    $add_button = [
      '#type' => 'link',
      '#title' => $this->t('Add vehicle'),
      '#url' => Url::fromRoute('inventory_system.vehicle_add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['inventory-list-container']],
      'add_button' => $add_button,
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No vehicles found.'),
      ],
    ];

  }

  /**
   * Delete a vehicle.
   * 
   * @param int $vid
   * 
   */

  public function deleteVehicle($vid) {
    $connection = Database::getConnection();
    $query = $connection->delete('vehicles')
      ->condition('vehicle_id', $vid)
      ->execute();

    $this->messenger->addMessage($this->t('The vehicle has been deleted.'));

    return $this->redirect('inventory_system.vehicles_list');
  }

}