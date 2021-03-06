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

namespace Drupal\apigee_edge\Controller;

use Drupal\apigee_edge\Form\DeveloperAppBaseFieldConfigForm;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\field_ui\Controller\FieldConfigListController;

/**
 * Defines a controller to list Developer App field instances.
 */
class DeveloperAppFieldConfigListController extends FieldConfigListController {

  /**
   * {@inheritdoc}
   */
  public function listing($entity_type_id = NULL, $bundle = NULL, RouteMatchInterface $route_match = NULL) {
    $page = parent::listing($entity_type_id, $bundle, $route_match);

    $page['base_field_config'] = $this->formBuilder()->getForm(DeveloperAppBaseFieldConfigForm::class);

    return $page;
  }

}
