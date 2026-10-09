<?php

namespace Aurora\Modules\Dav\Tests\Unit;

use Afterlogic\DAV\CalDAV\Schedule\IMipPlugin;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Message as ITipMessage;
use PHPUnit\Framework\TestCase;

/**
 * Tests which locally delivered iTIP messages IMipPlugin::schedule() still sends by email
 */
class CalDAVIMipPluginMethodTest extends TestCase
{
    private function isEmailAlreadySentByWebmail(string $method, bool $davRequest): bool
    {
        $plugin = new IMipPlugin();
        $reflectionMethod = (new \ReflectionClass($plugin))->getMethod('isEmailAlreadySentByWebmail');

        $iTipMessage = new ITipMessage();
        $iTipMessage->method = $method;

        return $reflectionMethod->invoke($plugin, $iTipMessage, $davRequest);
    }

    public function testRequestIsEmailedForWebmailAndDavChanges(): void
    {
        $this->assertFalse($this->isEmailAlreadySentByWebmail('REQUEST', false));
        $this->assertFalse($this->isEmailAlreadySentByWebmail('REQUEST', true));
    }

    public function testReplyAndCancelFromDavClientAreEmailed(): void
    {
        $this->assertFalse($this->isEmailAlreadySentByWebmail('REPLY', true));
        $this->assertFalse($this->isEmailAlreadySentByWebmail('CANCEL', true));
    }

    public function testReplyAndCancelFromWebmailAreNotEmailedTwice(): void
    {
        $this->assertTrue($this->isEmailAlreadySentByWebmail('REPLY', false));
        $this->assertTrue($this->isEmailAlreadySentByWebmail('CANCEL', false));
        $this->assertTrue($this->isEmailAlreadySentByWebmail('cancel', false));
    }
}

/**
 * Test the IMipPlugin's isEventInPast method directly
 */
class CalDAVIMipPluginEventPastTest extends TestCase
{
    private $plugin;

    protected function setUp(): void
    {
        $this->plugin = new IMipPlugin();
    }

    public function testIsEventInPastWithPastEvent(): void
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('isEventInPast');

        $iTipMessage = new ITipMessage();
        $vCal = new VCalendar();
        /** @var \Sabre\VObject\Component\VEvent $event */
        $event = $vCal->add('VEVENT');
        $event->add('UID', 'test-uid');
        $event->add('DTSTART', new \DateTime('2020-01-01 10:00:00', new \DateTimeZone('UTC')));
        $event->add('DTEND', new \DateTime('2020-01-01 11:00:00', new \DateTimeZone('UTC')));
        $event->add('SUMMARY', 'Test Event');
        $iTipMessage->message = $vCal;

        $result = $method->invoke($this->plugin, $iTipMessage);
        $this->assertTrue($result, 'Event in 2020 should be in the past');
    }

    public function testIsEventInPastWithFutureEvent(): void
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('isEventInPast');

        $iTipMessage = new ITipMessage();
        $vCal = new VCalendar();
        /** @var \Sabre\VObject\Component\VEvent $event */
        $event = $vCal->add('VEVENT');
        $event->add('UID', 'test-uid');
        $event->add('DTSTART', new \DateTime('+1 year 10:00:00', new \DateTimeZone('UTC')));
        $event->add('DTEND', new \DateTime('+1 year 11:00:00', new \DateTimeZone('UTC')));
        $event->add('SUMMARY', 'Future Event');
        $iTipMessage->message = $vCal;

        $result = $method->invoke($this->plugin, $iTipMessage);
        $this->assertFalse($result, 'Event in the future should not be in the past');
    }

    public function testIsEventInPastWithRecurringEvent(): void
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('isEventInPast');

        $iTipMessage = new ITipMessage();
        $vCal = new VCalendar();
        /** @var \Sabre\VObject\Component\VEvent $event */
        $event = $vCal->add('VEVENT');
        $event->add('UID', 'test-uid');
        $event->add('DTSTART', new \DateTime('2020-01-01 10:00:00', new \DateTimeZone('UTC')));
        $event->add('DTEND', new \DateTime('2020-01-01 11:00:00', new \DateTimeZone('UTC')));
        $event->add('RRULE', 'FREQ=WEEKLY;COUNT=10');
        $event->add('SUMMARY', 'Recurring Event');
        $iTipMessage->message = $vCal;

        $result = $method->invoke($this->plugin, $iTipMessage);
        $this->assertFalse($result, 'Recurring event with future occurrences should not be in the past');
    }

    public function testIsEventInPastWithNoDtStart(): void
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('isEventInPast');

        $iTipMessage = new ITipMessage();
        $vCal = new VCalendar();
        /** @var \Sabre\VObject\Component\VEvent $event */
        $event = $vCal->add('VEVENT');
        $event->add('UID', 'test-uid');
        $event->add('SUMMARY', 'Event without DTSTART');
        $iTipMessage->message = $vCal;

        $result = $method->invoke($this->plugin, $iTipMessage);
        $this->assertFalse($result, 'Event without DTSTART should not be considered in the past');
    }
}
