<?php
include_once('doctype.inc');
print '<title>Devec/Demography Data Portal</title>';
include_once('analytics.inc');
print '</head>';
?>

<body>
<nav>
  <a href="/">Home</a>,
  <a href="/region.php">Region</a>
</nav>
<p>Welcome to the <strong>Devec/Demography Data Portal</strong>, hosted by <a href="https://vipulnaik.com/">Vipul Naik</a>, with contributions from <a href="https://issarice.com">Issa Rice</a> (<a href="https://contractwork.vipulnaik.com/venue.php?venue=Devec%2FDemography+data+portal&matching=exact">list of all financially compensated contributions to the site</a>). The purpose of the site is to offer a visualization of development and demography trends as gathered from multiple sources, placing all sources side by side so that we can understand their similarities and differences.</p>

<p>This effort is complementary to the <a href="https://devec.subwiki.org/">devec subwiki</a> and <a href="https://demography.subwiki.org/">demography subwiki</a>.</p>

<p>The code for the portal is available on GitHub at <a href="https://github.com/riceissa/devec-demography-data-portal">riceissa/devec-demography-data-portal</a>. The data-crunching code for various data sources used is in separate repositories, all linked from the README of the portal.</p>

<strong>Endpoint documentations</strong>

<?php
include_once("regionblurb.inc");
?>
</body>
