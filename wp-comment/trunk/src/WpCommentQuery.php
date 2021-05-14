<?php

declare(strict_types=1);

namespace Pollen\WpComment;

use Pollen\WpPost\WpPostQuery;
use Pollen\WpPost\WpPostQueryInterface;
use Pollen\WpUser\WpUserQuery;
use Pollen\WpUser\WpUserQueryInterface;
use Pollen\Support\DateTime;
use Pollen\Support\ParamsBag;
use WP_Comment;
use WP_Comment_Query;

class WpCommentQuery extends ParamsBag implements WpCommentQueryInterface
{
    /**
     * Nom de qualification ou liste de types associés.
     * @var string|array
     */
    protected static $type = [];

    /**
     * Instance de la dernière requête de récupération d'une liste d'éléments.
     * @var ParamsBag|null
     */
    protected static $query;

    /**
     * Liste des arguments de requête de récupération des éléments par défaut.
     * @var array
     */
    protected static $defaultArgs = [];

    /**
     * Instance de commentaire Wordpress.
     * @var WP_Comment
     */
    protected $wp_comment;

    /**
     * @param WP_Comment|null $wp_comment
     *
     * @return void
     */
    public function __construct(?WP_Comment $wp_comment = null)
    {
        if ($this->wp_comment = $wp_comment instanceof WP_Comment ? $wp_comment : null) {
            parent::__construct($this->wp_comment->to_array());
        }
    }

    /**
     * @inheritDoc
     */
    public static function createFromId(int $comment_id): ?WpCommentQueryInterface
    {
        return (($wp_comment = get_comment($comment_id)) && ($wp_comment instanceof WP_Comment))
            ? new static($wp_comment) : null;
    }

    /**
     * @inheritDoc
     */
    public static function fetchFromArgs(array $args = []): array
    {
        return static::fetchFromWpCommentQuery(new WP_Comment_Query(static::parseQueryArgs($args)));
    }

    /**
     * @inheritDoc
     */
    public static function fetchFromIds(array $ids): array
    {
        return static::fetchFromWpCommentQuery(new WP_Comment_Query(static::parseQueryArgs(['comment__in' => $ids])));
    }

    /**
     * @inheritDoc
     */
    public static function fetchFromWpCommentQuery(WP_Comment_Query $wp_comment_query): array
    {
        if ($comments = $wp_comment_query->comments) {
            array_walk($comments, function (WP_Comment &$wp_comment) {
                $wp_comment = new static($wp_comment);
            });
        } else {
            $comments = [];
        }

        return $comments;
    }

    /**
     * @inheritDoc
     */
    public static function parseQueryArgs(array $args = []): array
    {
        if ($type = static::$type) {
            $args['type'] = $type;
        }

        return array_merge(static::$defaultArgs, $args);
    }

    /**
     * @inheritDoc
     */
    public static function setDefaultArgs(array $args): void
    {
        self::$defaultArgs = $args;
    }

    /**
     * @inheritDoc
     */
    public static function setType($type): void
    {
        self::$type = $type;
    }

    /**
     * @inheritDoc
     */
    public function getAgent(): string
    {
        return $this->get('comment_agent', '');
    }

    /**
     * @inheritDoc
     */
    public function getAuthor(): string
    {
        return $this->get('comment_author', '');
    }

    /**
     * @inheritDoc
     */
    public function getAuthorEmail(): string
    {
        return $this->get('comment_author_email', '');
    }

    /**
     * @inheritDoc
     */
    public function getAuthorIp(): string
    {
        return $this->get('comment_author_ip', '');
    }

    /**
     * @inheritDoc
     */
    public function getAuthorUrl(): string
    {
        return $this->get('comment_author_url', '');
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return $this->get('comment_content', '');
    }

    /**
     * @inheritDoc
     */
    public function getDate(bool $gmt = false): string
    {
        return $gmt
            ? (string)$this->get('comment_date_gmt', '')
            : (string)$this->get('comment_date', '');
    }

    /**
     * @inheritDoc
     */
    public function getDateTime(bool $gmt = false): DateTime
    {
        return Datetime::createFromTimeString($this->getDate($gmt));
    }

    /**
     * @inheritDoc
     */
    public function getEditUrl(): string
    {
        return get_edit_comment_link($this->getId());
    }

    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return (int) $this->get('comment_ID', 0);
    }

    /**
     * @inheritDoc
     */
    public function getMeta(string $meta_key, bool $single = false, $default = null)
    {
        return get_comment_meta($this->getId(), $meta_key, $single) ?: $default;
    }

    /**
     * @inheritDoc
     */
    public function getMetaMulti(string $meta_key, $default = null)
    {
        return $this->getMeta($meta_key, false, $default);
    }

    /**
     * @inheritDoc
     */
    public function getMetaSingle(string $meta_key, $default = null)
    {
        return $this->getMeta($meta_key, true, $default);
    }

    /**
     * @inheritDoc
     */
    public function getQueriedParent(): ?WpCommentQueryInterface
    {
        return self::createFromId($this->getParentId());
    }

    /**
     * @inheritDoc
     */
    public function getParentId(): int
    {
        return (int)$this->get('comment_parent', 0);
    }

    /**
     * @inheritDoc
     */
    public function getQueriedPost(): WpPostQueryInterface
    {
        return WpPostQuery::createFromId($this->getPostId());
    }

    /**
     * @inheritDoc
     */
    public function getPostId(): int
    {
        return (int) $this->get('comment_post_ID', 0);
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->get('comment_type', '');
    }

    /**
     * @inheritDoc
     */
    public function getQueriedUser(): WpUserQueryInterface
    {
        return WpUserQuery::createFromId($this->getUserId());
    }

    /**
     * @inheritDoc
     */
    public function getUserId(): int
    {
        return (int)$this->get('user_id', 0);
    }

    /**
     * @inheritDoc
     */
    public function getWpComment(): WP_Comment
    {
        return $this->wp_comment;
    }

    /**
     * @inheritDoc
     */
    public function isApproved(): bool
    {
        return $this->get('comment_approved', 0) === 1;
    }

    /**
     * @inheritDoc
     */
    public function isSpam(): bool
    {
        return $this->get('comment_approved', '') === 'spam';
    }

    /**
     * @inheritDoc
     */
    public function typeIn(array $comment_types): bool
    {
        return in_array($this->getType(), $comment_types, true);
    }
}