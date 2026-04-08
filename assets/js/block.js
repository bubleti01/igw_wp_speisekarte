(function (blocks, element, serverSideRender) {
  const el = element.createElement;
  const ServerSideRender = serverSideRender;

  blocks.registerBlockType('igw/speisekarte-home', {
    edit: function () {
      return el(ServerSideRender, {
        block: 'igw/speisekarte-home'
      });
    },
    save: function () {
      return null;
    }
  });
})(window.wp.blocks, window.wp.element, window.wp.serverSideRender);
