<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Post\CommentPost;
use Flarum\User\Gate;

class PostSerializer extends BasicPostSerializer
{
    /**
     * @var \Flarum\User\Gate
     */
    protected $gate;

    /**
     * @param \Flarum\User\Gate $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($post)
    {
        $attributes = parent::getDefaultAttributes($post);

        unset($attributes['content']);

        $gate = $this->gate->forUser($this->actor);

        $canEdit = $gate->allows('edit', $post);

        if ($post instanceof CommentPost) {
            $attributes['contentHtml'] = $post->content_html;

            if ($canEdit) {
                $attributes['content'] = $post->content;
            }
            if ($gate->allows('viewIps', $post)) {
                $attributes['ipAddress'] = $post->ip_address;
            }
        } else {
            $attributes['content'] = $post->content;
        }

        if ($post->edit_time) {
            $attributes['editTime'] = $this->formatDate($post->edit_time);
        }

        if ($post->hide_time) {
            $attributes['isHidden'] = true;
            $attributes['hideTime'] = $this->formatDate($post->hide_time);
        }

        $attributes += [
            'canEdit'   => $canEdit,
            'canDelete' => $gate->allows('delete', $post),
            'canHide'   => $gate->allows('hide', $post)
        ];

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($post)
    {
        return $this->hasOne($post, UserSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function discussion($post)
    {
        return $this->hasOne($post, BasicDiscussionSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function editUser($post)
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function hideUser($post)
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }
}
