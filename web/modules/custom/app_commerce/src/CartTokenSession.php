<?php

namespace Drupal\app_commerce;

use Drupal\commerce_cart\CartSessionInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decorates the cart session to support cart tokens.
 */
final class CartTokenSession implements CartSessionInterface {

  const HEADER_NAME = 'Commerce-Cart-Token';
  const QUERY_NAME = 'cartToken';

  /**
   * The inner cart session service.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  private $inner;

  /**
   * Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  private $tempStore;

  /**
   * Constructs a new CartTokenSession object.
   *
   * @param \Drupal\commerce_cart\CartSessionInterface $inner
   *   The decorated cart session.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(CartSessionInterface $inner, RequestStack $request_stack, SharedTempStoreFactory $temp_store_factory) {
    $this->inner = $inner;
    $this->requestStack = $request_stack;
    $this->tempStore = $temp_store_factory->get('app_commerce_tokens');
  }

  /**
   * {@inheritdoc}
   */
  public function getCartIds($type = self::ACTIVE) {
    if ($this->getCurrentRequestCartToken() === NULL) {
      return $this->inner->getCartIds($type);
    }
    $data = $this->getTokenCartData();
    return $data[$type];
  }

  /**
   * {@inheritdoc}
   */
  public function addCartId($cart_id, $type = self::ACTIVE) {
    $this->inner->addCartId($cart_id, $type);

    if ($this->getCurrentRequestCartToken() !== NULL) {
      $data = $this->getTokenCartData();
      $ids = $data[$type];
      $ids[] = $cart_id;
      $data[$type] = $ids;
      $this->setTokenCartData($data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasCartId($cart_id, $type = self::ACTIVE) {
    if ($this->getCurrentRequestCartToken() === NULL) {
      return $this->inner->hasCartId($cart_id, $type);
    }
    $data = $this->getTokenCartData();
    $ids = $data[$type];
    return in_array($cart_id, $ids, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCartId($cart_id, $type = self::ACTIVE) {
    $this->inner->deleteCartId($cart_id, $type);

    if ($this->getCurrentRequestCartToken() !== NULL) {
      $data = $this->getTokenCartData();
      $ids = $data[$type];
      $ids = array_diff($ids, [$cart_id]);
      $data[$type] = $ids;
      $this->setTokenCartData($data);
    }
  }

  /**
   * Get the cart token from the request.
   *
   * @return string
   *   The cart token.
   */
  private function getCurrentRequestCartToken() {
    $request = $this->requestStack->getCurrentRequest();
    assert($request instanceof Request);
    return $request->headers->get(static::HEADER_NAME);
  }

  /**
   * Get the token cart data.
   *
   * @return array
   *   The data.
   */
  private function getTokenCartData() {
    $defaults = [
      static::ACTIVE => [],
      static::COMPLETED => [],
    ];
    $token = $this->getCurrentRequestCartToken();
    if (empty($token)) {
      return $defaults;
    }
    return $this->tempStore->get($token) ?: $defaults;
  }

  /**
   * Set the token cart data.
   *
   * @param array $data
   *   The data.
   */
  private function setTokenCartData(array $data) {
    $token = $this->getCurrentRequestCartToken();
    if (!empty($token)) {
      $maxRetries = 3;
      $retryCount = 0;
      $success = FALSE;
      
      while (!$success && $retryCount < $maxRetries) {
        try {
          $this->tempStore->set($token, $data);
          $success = TRUE;
        }
        catch (\Exception $e) {
          // Log the error
          \Drupal::logger('app_commerce')->warning('Failed to set cart token data (attempt @count): @message', [
            '@count' => $retryCount + 1,
            '@message' => $e->getMessage(),
          ]);
          $retryCount++;
          if ($retryCount < $maxRetries) {
            // Wait a bit before retrying (increasing backoff)
            usleep(100000 * $retryCount); // 100ms, 200ms, 300ms
          }
        }
      }
      
      if (!$success) {
        // Log the final failure but don't throw an exception to allow the operation to continue
        \Drupal::logger('app_commerce')->error('Failed to set cart token data after @max_retries retries', [
          '@max_retries' => $maxRetries,
        ]);
      }
    }
  }

}
