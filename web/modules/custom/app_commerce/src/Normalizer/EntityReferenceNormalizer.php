<?php

namespace Drupal\app_commerce\Normalizer;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;

/**
 * Expands entity reference field values to their referenced entity.
 */
class EntityReferenceNormalizer extends EntityReferenceFieldItemNormalizer {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * An array of allowed fields for this normalizer.
   *
   * @var array
   */
  protected $allowedFields;

  /**
   * Constructs a new EntityReferenceNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param array $app_commerce
   *   The Cart API's service parameters.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, RouteMatchInterface $route_match, array $app_commerce) {
    parent::__construct($entity_repository);
    $this->routeMatch = $route_match;
    $this->allowedFields = $app_commerce['normalized_entity_references'];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL, array $context = []): bool {
    $supported = parent::supportsNormalization($data, $format);
    if ($supported) {
      $route = $this->routeMatch->getRouteObject();
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $data */
      $name = $data->getFieldDefinition()->getName();
      return $route && in_array($name, $this->getSupportedFields(), TRUE) && $route->hasRequirement('_cart_api');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($field_item instanceof EntityReferenceItem);
    $entity = $field_item->get('entity')->getValue();
    return $this->serializer->normalize($entity, $format, $context);
  }

  /**
   * Gets the supported fields by the normalizer.
   *
   * @return array
   *   An array of field names that are supported.
   */
  protected function getSupportedFields() {
    return $this->allowedFields;
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove this when Drupal 9.x support is dropped.
   */
  public function hasCacheableSupportsMethod(): bool {
    return FALSE;
  }

}
