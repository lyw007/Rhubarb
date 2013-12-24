<?php
namespace Rhubarb\Task;

/**
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * Copyright [2013] [Robert Allen]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package     Rhubarb
 * @category    Rhubarb
 * @subcategory AsyncResult
 */
use Rhubarb\Rhubarb;
use Rhubarb\Message\Message;
use Rhubarb\Exception\TimeoutException;

/**
 * @package     Rhubarb
 * @category    Rhubarb
 * @subcategory AsyncResult
 */
class AsyncResult
{
    const PENDING = 'PENDING';
    const STARTED = 'STARTED';
    const SUCCESS = 'SUCCESS';
    const FAILURE = 'FAILURE';
    const RETRY = 'RETRY';
    const REVOKED = 'REVOKED';

    static public $allowedResultStates = array(
        AsyncResult::PENDING,
        AsyncResult::FAILURE,
        AsyncResult::RETRY,
        AsyncResult::REVOKED,
        AsyncResult::STARTED,
        AsyncResult::SUCCESS
    );
    static public $completedState = array(
        AsyncResult::FAILURE,
        AsyncResult::REVOKED,
        AsyncResult::SUCCESS
    );
    /**
     * @var Rhubarb
     */
    protected $rhubarb;
    /**
     * @var Message
     */
    protected $message;
    /**
     * {"status": "SUCCESS", "traceback": null, "result": 4, "children": []}
     * @var ResultBody
     */
    protected $result;

    /**
     * @param Rhubarb $rhubarb
     * @param Message $message
     * @param ResultBody|array $result
     */
    public function __construct(Rhubarb $rhubarb, Message $message, $result = array())
    {
        $this->setRhubarb($rhubarb);
        $this->setMessage($message);
        $this->setResult(new ResultBody($result));
    }

    public function revoke()
    {
        //{"destination": null, "method": "revoke", "arguments": {"signal": null, "terminate": false, "task_id": "c72e99dd-13aa-4791-8b21-6ae94594972b"}}
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getMessage()->getId();
    }

    /**
     * @param Rhubarb $rhubarb
     *
     * @return AsyncResult
     */
    protected function setRhubarb(Rhubarb $rhubarb)
    {
        $this->rhubarb = $rhubarb;
        return $this;
    }

    /**
     * @return Rhubarb
     */
    protected function getRhubarb()
    {
        return $this->rhubarb;
    }

    /**
     * @return bool
     */
    public function isReady()
    {
        return in_array($this->getResult()->getState(), static::$completedState);
    }

    /**
     * @return bool
     */
    public function isStarted()
    {
        return $this->result->getState() == self::STARTED;
    }

    /**
     * @return bool
     */
    public function isRevoked()
    {
        return $this->result->getState() == self::REVOKED;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isReady() && $this->result->getState() == self::SUCCESS;
    }

    /**
     * @return bool
     */
    public function isRetry()
    {
        return $this->result->getState() == self::RETRY;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return $this->result->getState() == self::PENDING;
    }

    /**
     * @return bool
     */
    public function isFailure()
    {
        return $this->isReady() && $this->getResult()->getState() === self::FAILURE;
    }

    /**
     * @param int $timeout
     * @param float $interval
     *
     * @return mixed
     * @throws \Rhubarb\Exception\TimeoutException
     */
    public function get($timeout = 10, $interval = 0.5)
    {
        $intervalUs = (int)($interval * 1000000);
        $iterationLimit = (int)($timeout / $interval);

        for ($i = 0; $i < $iterationLimit; $i++) {
            if ($this->isReady()) {
                break;
            }
            usleep($intervalUs);
        }

        if (!$this->isReady()) {
            throw new TimeoutException(
                sprintf(
                    'AsyncResult %s(%s) did not return after %s seconds',
                    $this->getMessage()->getId(),
                    (string)$this->getMessage(),
                    $timeout
                )
            );
        }
        return $this->getResult();
    }

    /**
     *
     * @param \Rhubarb\Message\Message $message
     * @return AsyncResult
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return \Rhubarb\Message\Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     *
     * @param ResultBody $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return ResultBody
     */
    public function getResult()
    {
        if (!in_array($this->result->getState(), self::$completedState)) {
            $this->getRhubarb()->getResultStore()
                ->getTaskResult($this);
        }
        return $this->result;
    }

}
