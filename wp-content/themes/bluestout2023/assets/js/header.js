$(function() {
  function changeHeader(){
    var header = $('#main_header.home');

    $(window).scroll(function(){
      if ($(window).scrollTop() > 5){
        header.addClass('scrolled');
      } else {
        header.removeClass('scrolled');
      }
    });
  }
  changeHeader();

  $('.mobile-menu-toggle').on('click', function() {
    if($('.mobile-menu-toggle').hasClass('menu-oppend')) {
      $('.mobile-menu-toggle').removeClass('menu-oppend');
      $('.mobile-menu').removeClass('menu-oppend');
      $('body').removeClass('scroll-disabled');
    } else {
      $('.mobile-menu-toggle').addClass('menu-oppend');
      $('.mobile-menu').addClass('menu-oppend');
      $('body').addClass('scroll-disabled');
    }
  });

  $('#main_header .nav-item').on('mouseenter', function() {
    $('#main_header .nav-item').removeClass('show');
    $('#main_header .nav-item').removeClass('mouse-leave');
    $(this).addClass('show');
  });

  $('#main_header .nav-item').on('mouseleave', function() {
    $(this).addClass('mouse-leave');
    let _this = this;
    setTimeout(function() {
      if($(_this).hasClass('mouse-leave')) {
        $(_this).removeClass('show');
        $(_this).removeClass('mouse-leave');
      }
    }, 100);
  });
});
