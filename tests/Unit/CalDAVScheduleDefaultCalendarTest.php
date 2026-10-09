<?php

namespace Aurora\Modules\Dav\Tests\Unit;

use Afterlogic\DAV\CalDAV\Calendar;
use Afterlogic\DAV\CalDAV\Schedule\Plugin;
use PHPUnit\Framework\TestCase;
use Sabre\CalDAV\Backend\BackendInterface;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

/**
 * Tests which calendar Schedule\Plugin::scheduleLocalDelivery() delivers
 * scheduling messages to (Plugin::getScheduleDefaultCalendar())
 */
class CalDAVScheduleDefaultCalendarTest extends TestCase
{
    private function calendar(string $uri, ?array $components): Calendar
    {
        $calendarInfo = ['id' => [1, 1], 'uri' => $uri, 'principaluri' => 'principals/user@example.com'];
        if ($components !== null) {
            $calendarInfo['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'] = new SupportedCalendarComponentSet($components);
        }

        return new Calendar($this->createStub(BackendInterface::class), $calendarInfo);
    }

    private function getScheduleDefaultCalendar(array $children)
    {
        $home = new class ($children) {
            private $children;

            public function __construct(array $children)
            {
                $this->children = $children;
            }

            public function getChildren()
            {
                return $this->children;
            }
        };

        $plugin = new Plugin();
        $method = (new \ReflectionClass($plugin))->getMethod('getScheduleDefaultCalendar');

        return $method->invoke($plugin, $home);
    }

    public function testDefaultCalendarIsPreferred(): void
    {
        $other = $this->calendar('work', ['VEVENT', 'VTODO']);
        $default = $this->calendar('MyCalendar-1234', ['VEVENT', 'VTODO']);

        $this->assertSame($default, $this->getScheduleDefaultCalendar([$other, $default]));
    }

    public function testCalendarWithoutVeventIsSkipped(): void
    {
        $reminders = $this->calendar('reminders', ['VTODO']);
        $events = $this->calendar('work', ['VEVENT']);

        $this->assertSame($events, $this->getScheduleDefaultCalendar([$reminders, $events]));
    }

    public function testDefaultCalendarWithoutVeventIsSkipped(): void
    {
        $default = $this->calendar('MyCalendar-1234', ['VTODO']);
        $events = $this->calendar('work', ['VEVENT']);

        $this->assertSame($events, $this->getScheduleDefaultCalendar([$default, $events]));
    }

    public function testCalendarWithoutComponentSetSupportsEvents(): void
    {
        $calendar = $this->calendar('work', null);
        $emptySet = $this->calendar('home', []);

        $this->assertSame($calendar, $this->getScheduleDefaultCalendar([$calendar]));
        $this->assertSame($emptySet, $this->getScheduleDefaultCalendar([$emptySet]));
    }

    public function testNoCalendarForEvents(): void
    {
        $this->assertNull($this->getScheduleDefaultCalendar([]));
        $this->assertNull($this->getScheduleDefaultCalendar([$this->calendar('reminders', ['VTODO'])]));
    }
}
