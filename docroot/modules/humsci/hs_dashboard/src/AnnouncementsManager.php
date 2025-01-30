<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard;

/**
 * Class to handle HDSP Announcements.
 */
final class AnnouncementsManager {

  /**
   * @todo Add method description.
   */
  public function getTableHeader(): array {
    $tableHeader = [
      [
        'data' => 'Date',
      ],
      [
        'data' => 'Title',
      ],
      [
        'data' => 'Description',
      ],
    ];

    return $tableHeader;
  }

  /**
   * @todo Add method description.
   */
  public function getTableRows(): array {
    $tableRows = [
      [
        'data' => [
          [
            'data' => '01-30-2025 15:11:01',
          ],
          [
            'data' => 'A happy little stream',
          ],
          [
            'data' => 'Citizens of distant epochs worldlets ship of the imagination light years finite but unbounded, star stuff harvesting star light. The carbon in our apple pies, shores of the cosmic ocean brain is the seed of intelligence a very small stage in a vast cosmic arena of brilliant syntheses tendrils of gossamer clouds. A very small stage in a vast cosmic arena. Colonies. Evidence. Science and billions upon billions upon billions upon billions upon billions upon billions upon billions.',
          ],
        ],
      ],
      [
        'data' => [
          [
            'data' => '01-30-2025 15:11:11',
          ],
          [
            'data' => 'White mazagran',
          ],
          [
            'data' => 'At grounds mocha single shot cup so kopi-luwak affogato coffee flavour. Flavour, id, caramelization, sit, flavour robusta ristretto frappuccino white mazagran. As saucer, americano, con panna cup cortado cappuccino sit espresso. Turkish, white, turkish steamed con panna doppio grinder grounds. Crema aroma decaffeinated whipped carajillo cinnamon to go.',
          ],
        ],
      ],
    ];

    return $tableRows;
  }

}
