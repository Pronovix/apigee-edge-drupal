<?php

/**
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_edge\Entity;

use Apigee\Edge\Api\Management\Entity\DeveloperApp as EdgeDeveloperApp;
use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\apigee_edge\Exception\InvalidArgumentException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the Developer app entity class.
 *
 * @\Drupal\apigee_edge\Annotation\EdgeEntityType(
 *   id = "developer_app",
 *   label = @Translation("Developer App"),
 *   label_singular = @Translation("Developer App"),
 *   label_plural = @Translation("Developer Apps"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Developer App",
 *     plural = "@count Developer Apps",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\apigee_edge\Entity\Storage\DeveloperAppStorage",
 *     "access" = "Drupal\apigee_edge\Entity\EdgeEntityAccessControlHandler",
 *     "permission_provider" = "Drupal\apigee_edge\Entity\DeveloperAppPermissionProvider",
 *     "form" = {
 *       "default" = "Drupal\apigee_edge\Entity\Form\DeveloperAppCreateForm",
 *       "add" = "Drupal\apigee_edge\Entity\Form\DeveloperAppCreateForm",
 *       "add_for_developer" = "Drupal\apigee_edge\Entity\Form\DeveloperAppCreateFormForDeveloper",
 *       "edit" = "Drupal\apigee_edge\Entity\Form\DeveloperAppEditForm",
 *       "edit_for_developer" = "Drupal\apigee_edge\Entity\Form\DeveloperAppEditFormForDeveloper",
 *       "delete" = "Drupal\apigee_edge\Entity\Form\DeveloperAppDeleteForm",
 *       "delete_for_developer" = "Drupal\apigee_edge\Entity\Form\DeveloperAppDeleteFormForDeveloper",
 *       "analytics" = "Drupal\apigee_edge\Form\DeveloperAppAnalyticsForm",
 *       "analytics_for_developer" = "Drupal\apigee_edge\Form\DeveloperAppAnalyticsFormForDeveloper",
 *     },
 *     "list_builder" = "Drupal\apigee_edge\Entity\ListBuilder\DeveloperAppListBuilder",
 *   },
 *   links = {
 *     "canonical" = "/developer-apps/{developer_app}",
 *     "collection" = "/developer-apps",
 *     "add-form" = "/developer-apps/add",
 *     "edit-form" = "/developer-apps/{developer_app}/edit",
 *     "delete-form" = "/developer-apps/{developer_app}/delete",
 *     "analytics" = "/developer-apps/{developer_app}/analytics",
 *     "canonical-by-developer" = "/user/{user}/apps/{app}",
 *     "collection-by-developer" = "/user/{user}/apps",
 *     "add-form-for-developer" = "/user/{user}/create-app",
 *     "edit-form-for-developer" = "/user/{user}/apps/{app}/edit",
 *     "delete-form-for-developer" = "/user/{user}/apps/{app}/delete",
 *     "analytics-for-developer" = "/user/{user}/apps/{app}/analytics",
 *   },
 *   entity_keys = {
 *     "id" = "appId",
 *   },
 *   query_class = "Drupal\apigee_edge\Entity\Query\DeveloperAppQuery",
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer developer_app",
 *   field_ui_base_route = "apigee_edge.settings.app",
 * )
 */
class DeveloperApp extends App implements DeveloperAppInterface {

  /**
   * The cached Drupal UID.
   *
   * Use getOwnerId() to return the correct value.
   *
   * @var null|int
   */
  protected $drupalUserId;

  /**
   * The decorated developer app entity from the SDK.
   *
   * @var \Apigee\Edge\Api\Management\Entity\DeveloperApp
   */
  protected $decorated;

  /**
   * DeveloperApp constructor.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   * @param null|string $entity_type
   *   Type of the entity. It is optional because constructor sets its default
   *   value.
   * @param \Apigee\Edge\Entity\EntityInterface|null $decorated
   *   The SDK entity that this Drupal entity decorates.
   */
  public function __construct(array $values, ?string $entity_type = NULL, ?EdgeEntityInterface $decorated = NULL) {
    // Little help to make it easier to initialize a new developer app object
    // with a property configured developerId which is required in app
    // creation.
    // @see DeveloperAppEntityControllerProxy::create()
    if (isset($values['drupalUserId'])) {
      $this->setOwnerId($values['drupalUserId']);
    }
    /** @var \Apigee\Edge\Api\Management\Entity\DeveloperAppInterface $decorated */
    $entity_type = $entity_type ?? 'developer_app';
    parent::__construct($values, $entity_type, $decorated);
  }

  /**
   * We have to override this.
   *
   * This is how we could make it compatible with the SDK's
   * entity interface that has return type hint.
   */
  public function id(): ?string {
    return parent::id();
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $ownerId = $this->getOwnerId();
    return $ownerId === NULL ? NULL : User::load($ownerId);
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->setOwnerId($account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    if ($this->drupalUserId === NULL) {
      if ($this->getDeveloperId()) {
        $developer = Developer::load($this->getDeveloperId());
        if ($developer) {
          /** @var \Drupal\user\Entity\UserInterface $account */
          $account = user_load_by_mail($developer->getEmail());
          if ($account) {
            $this->drupalUserId = $account->id();
          }
        }
      }
    }

    return $this->drupalUserId;
  }

  /**
   * Sets the entity owner's user ID.
   *
   * @param int $uid
   *   The owner user id.
   *
   * @return $this
   */
  public function setOwnerId($uid) {
    $this->drupalUserId = $uid;
    $user = User::load($uid);
    if ($user) {
      $developer = Developer::load($user->getEmail());
      if ($developer) {
        $this->decorated->setDeveloperId($developer->uuid());
      }
      else {
        // Sanity check, probably someone called this method with invalid data.
        throw new InvalidArgumentException("Developer with {$user->getEmail()} email does not exist on Apigee Edge.");
      }
    }
    else {
      // Sanity check, probably someone called this method with invalid data.
      throw new InvalidArgumentException("User with {$uid} id does not exist.");
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected static function decoratedClass(): string {
    return EdgeDeveloperApp::class;
  }

  /**
   * {@inheritdoc}
   */
  public static function idProperty(): string {
    return EdgeDeveloperApp::idProperty();
  }

  /**
   * {@inheritdoc}
   */
  public static function uniqueIdProperties(): array {
    return array_merge(parent::uniqueIdProperties(), ['appId']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAppOwner(): string {
    return $this->getDeveloperId();
  }

  /**
   * {@inheritdoc}
   */
  public function getDeveloperId(): ?string {
    return $this->decorated->getDeveloperId();
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToBaseFieldTypeMap(): array {
    return [
      // UUIDs (developerId, appId) managed on Apigee Edge so we do not
      // want to expose them as UUID fields. Same applies for createdAt and
      // lastModifiedAt. We do not want that Drupal apply default values
      // on them if they are empty therefore their field type is a simple
      // "timestamp" instead of "created" or "changed".
      'apiResources' => 'list_string',
      'apps' => 'list_string',
      'companies' => 'list_string',
      'createdAt' => 'timestamp',
      'description' => 'string_long',
      'environments' => 'list_string',
      'expiresAt' => 'timestamp',
      'issuedAt' => 'timestamp',
      'lastModifiedAt' => 'timestamp',
      'proxies' => 'list_string',
      'scopes' => 'list_string',
      'status' => 'string',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToBaseFieldBlackList(): array {
    return array_merge(parent::propertyToBaseFieldBlackList(), [
      // We expose each attribute as a field.
      'attributes',
      // We expose credentials as a pseudo field.
      'credentials',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = parent::baseFieldDefinitions($entity_type);
    $developer_app_singular_label = \Drupal::entityTypeManager()->getDefinition('developer_app')->getSingularLabel();

    $definitions['displayName']
      ->setLabel(t('@developer_app name', ['@developer_app' => $developer_app_singular_label]));

    $definitions['status']
      ->setLabel(t('@developer_app status', ['@developer_app' => $developer_app_singular_label]));

    $developer_app_settings = \Drupal::config('apigee_edge.developer_app_settings');
    foreach ((array) $developer_app_settings->get('required_base_fields') as $required) {
      $definitions[$required]->setRequired(TRUE);
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = parent::label();
    // Return app name instead of app id if display name is missing.
    if ($label === $this->id()) {
      $label = $this->getName();
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $params = parent::urlRouteParameters($rel);

    $for_developer_routes = [
      'canonical-by-developer',
      'edit-form-for-developer',
      'delete-form-for-developer',
      'analytics-for-developer',
    ];
    if ($rel === 'add-form-for-developer') {
      $params['user'] = $this->getOwnerId();
      unset($params['developer_app']);
    }
    elseif ($rel === 'collection-by-developer') {
      $params['user'] = $this->getOwnerId();
      unset($params['developer_app']);
    }
    elseif (in_array($rel, $for_developer_routes)) {
      $params['user'] = $this->getOwnerId();
      $params['app'] = $this->getName();
      unset($params['developer_app']);
    }
    elseif ($rel === 'add-form') {
      unset($params['developerId']);
    }

    return $params;
  }

}
