<?php

namespace Aurora\Modules\Dav\Tests\Unit;

use Afterlogic\DAV\CalDAV\Schedule\IMipPlugin;
use PHPUnit\Framework\TestCase;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Message as ITipMessage;

/**
 * Tests IMipPlugin::schedule() for the cases that are decided before an email is composed,
 * so no mail server or database is needed.
 *
 * A message that got the "1.2" status (delivered locally by Schedule\Plugin::scheduleLocalDelivery())
 * must keep it: the plugin either sends only a notification or does nothing.
 */
class CalDAVIMipPluginScheduleTest extends TestCase
{
    private const DELIVERED_LOCALLY = '1.2;Message delivered locally';

    private function message(string $method, string $start, ?string $scheduleStatus, bool $significantChange = true): ITipMessage
    {
        $vCal = new VCalendar();
        /** @var \Sabre\VObject\Component\VEvent $event */
        $event = $vCal->add('VEVENT');
        $event->add('UID', 'test-uid');
        $event->add('DTSTART', new \DateTime($start, new \DateTimeZone('UTC')));
        $event->add('DTEND', new \DateTime($start . ' +1 hour', new \DateTimeZone('UTC')));
        $event->add('SUMMARY', 'Test Event');

        $message = new ITipMessage();
        $message->method = $method;
        $message->uid = 'test-uid';
        $message->sender = 'mailto:organizer@example.com';
        $message->recipient = 'mailto:attendee@example.com';
        $message->message = $vCal;
        $message->significantChange = $significantChange;
        $message->scheduleStatus = $scheduleStatus;

        return $message;
    }

    public function testLocallyDeliveredReplyFromWebmailIsNotEmailedAgain(): void
    {
        // CalendarMeetingsPlugin has already emailed the reply. If schedule() did not return
        // here, it would go on to compose the email and would need the database.
        $message = $this->message('REPLY', '+1 year', self::DELIVERED_LOCALLY);

        (new IMipPlugin())->schedule($message);

        $this->assertSame(self::DELIVERED_LOCALLY, $message->scheduleStatus);
    }

    public function testLocallyDeliveredCancelFromWebmailIsNotEmailedAgain(): void
    {
        $message = $this->message('CANCEL', '+1 year', self::DELIVERED_LOCALLY);

        (new IMipPlugin())->schedule($message);

        $this->assertSame(self::DELIVERED_LOCALLY, $message->scheduleStatus);
    }

    public function testLocallyDeliveredMessageForPastEventKeepsItsStatus(): void
    {
        $message = $this->message('REQUEST', '2020-01-01 10:00:00', self::DELIVERED_LOCALLY);

        (new IMipPlugin())->schedule($message);

        $this->assertSame(self::DELIVERED_LOCALLY, $message->scheduleStatus);
    }

    public function testMessageForPastEventIsNotDelivered(): void
    {
        $message = $this->message('REQUEST', '2020-01-01 10:00:00', null);

        (new IMipPlugin())->schedule($message);

        $this->assertSame('5.3;Event is in the past; iTip delivery suppressed', $message->scheduleStatus);
    }

    public function testInsignificantChangeIsNotEmailed(): void
    {
        $message = $this->message('REQUEST', '+1 year', null, false);

        (new IMipPlugin())->schedule($message);

        $this->assertStringStartsWith('1.0;', $message->scheduleStatus);
    }

    public function testLocallyDeliveredInsignificantChangeKeepsItsStatus(): void
    {
        $message = $this->message('REQUEST', '+1 year', self::DELIVERED_LOCALLY, false);

        (new IMipPlugin())->schedule($message);

        $this->assertSame(self::DELIVERED_LOCALLY, $message->scheduleStatus);
    }
}
