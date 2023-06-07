<?
  $strategy_thank_you_title = get_field('strategy_thank_you_title');
  $strategy_thank_you_text = get_field('strategy_thank_you_text');
?>

<style>
  .strategy-thank-you .container {
      padding-top: 130px;
      padding-bottom: 130px;
      max-width: 924px;
      margin-left: auto;
      margin-right: auto;
  }

  .strategy-thank-you .title {
      margin-top: 30px;
  }

  .strategy-thank-you .content {
      margin-top: 30px;
  }

  .strategy-thank-you .content p {
      font-weight: 500;
  }

  .strategy-thank-you .action {
      margin-top: 20px;
  }

  .strategy-thank-you .action .btn {
      padding: 16px 30px 16px 30px;
  }

  @media (max-width: 767px) {
      .strategy-thank-you .container {
          padding: 30px 50px;
      }
      .strategy-thank-you .content p {
          font-size: 16px;
          font-weight: 400;
      }
      .strategy-thank-you .action .btn {
          width: 100%;
          padding: 16px;
      }
  }
</style>

<section class="strategy-thank-you page-hero">
  <div class="container text-center">
    <div class="title fadeInBottom-1">
      <h1 class="lg-title title-font"><?=$strategy_thank_you_title?></h1>
    </div>
    <div class="content fadeInBottom-2">
      <p class="xg-content"><?=$strategy_thank_you_text?></p>
    </div>
    <div class="action fadeInBottom-3">
      <a href="<?=get_full_url('/')?>" class="btn btn-blue blue-button-hover">Return to Homepage</a>
    </div>
  </div>
</section>