// Modified Isotope methods for gutters in masonry
jQuery.Isotope.prototype._getMasonryGutterColumns = function() {
var gutter = this.options.masonry && this.options.masonry.gutterWidth || 0;
containerWidth = this.element.width();

this.masonry.columnWidth = this.options.masonry && this.options.masonry.columnWidth ||
// Or use the size of the first item
this.$filteredAtoms.outerWidth(true) ||
// If there's no items, use size of container
containerWidth;

this.masonry.columnWidth += gutter;

this.masonry.cols = Math.floor((containerWidth + gutter) / this.masonry.columnWidth);
this.masonry.cols = Math.max(this.masonry.cols, 1);
};

jQuery.Isotope.prototype._masonryReset = function() {
// Layout-specific props
this.masonry = {};
// FIXME shouldn't have to call this again
this._getMasonryGutterColumns();
var i = this.masonry.cols;
this.masonry.colYs = [];
while (i--) {
this.masonry.colYs.push(0);
}
};

jQuery.Isotope.prototype._masonryResizeChanged = function() {
var prevSegments = this.masonry.cols;
// Update cols/rows
this._getMasonryGutterColumns();
// Return if updated cols/rows is not equal to previous
return (this.masonry.cols !== prevSegments);
};