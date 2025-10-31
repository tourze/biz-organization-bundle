<?php

declare(strict_types=1);

namespace BizOrganizationBundle\Tests\Service;

use Knp\Menu\ItemInterface;

/**
 * 测试用的 MenuItem 实现
 *
 * 注意：此类实现 Knp\Menu\ItemInterface 接口，该接口采用流式设计，要求setter方法返回ItemInterface以支持链式调用。
 * 为保持与第三方库接口的兼容性，所有setter方法都需要忽略NoReturnSetterMethodRule规则。
 */
class TestMenuItem implements ItemInterface
{
    /** @var array<string, ItemInterface> */
    private array $children = [];

    /** @var array<string, bool|string|null> */
    private array $attributes = [];

    private ?string $uri = null;

    private string $name = '';

    private ?string $label = null;

    public function getChild(string $name): ?ItemInterface
    {
        return $this->children[$name] ?? null;
    }

    public function addChild($child, array $options = []): ItemInterface
    {
        if (is_string($child)) {
            $newChild = new self();
            $this->children[$child] = $newChild;

            return $newChild;
        }

        return $this;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setUri(?string $uri): ItemInterface
    {
        $this->uri = $uri;

        return $this;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setAttribute(string $name, $value): ItemInterface
    {
        if (is_bool($value) || is_string($value) || null === $value) {
            $this->attributes[$name] = $value;
        }

        return $this;
    }

    /**
     * @param bool|string|null $default
     * @return bool|string|null
     */
    public function getAttribute(string $name, $default = null)
    {
        $value = $this->attributes[$name] ?? $default;
        if (is_bool($value) || is_string($value) || null === $value) {
            return $value;
        }

        return $default;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    // 实现其他必需的接口方法
    public function getName(): string
    {
        return $this->name;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setName(string $name): ItemInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->name;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setLabel(?string $label): ItemInterface
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return array<string, bool|string|null>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setAttributes(array $attributes): ItemInterface
    {
        /** @var array<string, bool|string|null> $attributes */
        $this->attributes = $attributes;

        return $this;
    }

    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function getLinkAttributes(): array
    {
        return [];
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setLinkAttributes(array $linkAttributes): ItemInterface
    {
        return $this;
    }

    public function getLinkAttribute(string $name, $default = null)
    {
        return $default;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setLinkAttribute(string $name, $value): ItemInterface
    {
        return $this;
    }

    public function getChildrenAttributes(): array
    {
        return [];
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setChildrenAttributes(array $childrenAttributes): ItemInterface
    {
        return $this;
    }

    public function getChildrenAttribute(string $name, $default = null)
    {
        return $default;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setChildrenAttribute(string $name, $value): ItemInterface
    {
        return $this;
    }

    public function getLabelAttributes(): array
    {
        return [];
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setLabelAttributes(array $labelAttributes): ItemInterface
    {
        return $this;
    }

    public function getLabelAttribute(string $name, $default = null)
    {
        return $default;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setLabelAttribute(string $name, $value): ItemInterface
    {
        return $this;
    }

    public function getExtras(): array
    {
        return [];
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setExtras(array $extras): ItemInterface
    {
        return $this;
    }

    public function getExtra(string $name, $default = null)
    {
        return $default;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setExtra(string $name, $value): ItemInterface
    {
        return $this;
    }

    public function getDisplayChildren(): bool
    {
        return true;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setDisplayChildren(bool $bool): ItemInterface
    {
        return $this;
    }

    public function isDisplayed(): bool
    {
        return true;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setDisplay(bool $bool): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function removeChild($name): ItemInterface
    {
        if (is_string($name) || $name instanceof ItemInterface) {
            $key = $name instanceof ItemInterface ? $name->getName() : $name;
            unset($this->children[$key]);
        }

        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setChildren(array $children): ItemInterface
    {
        /** @var array<string, ItemInterface> $children */
        $this->children = $children;

        return $this;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setCurrent(?bool $bool): ItemInterface
    {
        return $this;
    }

    public function isCurrent(): ?bool
    {
        return false;
    }

    public function isAncestor(): bool
    {
        return false;
    }

    public function getLevel(): int
    {
        return 0;
    }

    public function getRoot(): ItemInterface
    {
        return $this;
    }

    public function isRoot(): bool
    {
        return true;
    }

    public function getParent(): ?ItemInterface
    {
        return null;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setParent(?ItemInterface $parent = null): ItemInterface
    {
        return $this;
    }

    public function count(): int
    {
        return count($this->children);
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->children);
    }

    public function offsetExists($offset): bool
    {
        return is_string($offset) && isset($this->children[$offset]);
    }

    public function offsetGet($offset): ?ItemInterface
    {
        return is_string($offset) ? ($this->children[$offset] ?? null) : null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_string($offset) && $value instanceof ItemInterface) {
            $this->children[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        if (is_string($offset)) {
            unset($this->children[$offset]);
        }
    }

    public function actsLikeFirst(): bool
    {
        return true;
    }

    public function actsLikeLast(): bool
    {
        return true;
    }

    public function copy(): ItemInterface
    {
        return clone $this;
    }

    public function getFirstChild(): ItemInterface
    {
        $first = reset($this->children);

        return false !== $first ? $first : $this;
    }

    public function getLastChild(): ItemInterface
    {
        $last = end($this->children);

        return false !== $last ? $last : $this;
    }

    public function isFirst(): bool
    {
        return true;
    }

    public function isLast(): bool
    {
        return true;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function reorderChildren(array $order): ItemInterface
    {
        return $this;
    }

    /** @phpstan-ignore symplify.noReturnSetterMethod */
    public function setFactory(mixed $factory): ItemInterface
    {
        return $this;
    }
}
