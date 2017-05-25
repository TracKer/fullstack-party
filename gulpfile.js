var gulp = require('gulp');
var sass = require('gulp-sass');
var concat = require('gulp-concat');

gulp.task('img', function() {
  gulp.src('ui/img/**/*')
    .pipe(gulp.dest('public/dist/img'));
});

gulp.task('sass', function() {
  gulp.src('ui/sass/**/*.scss')
    .pipe(sass())
    // .pipe(concat('main.css'))
    .pipe(gulp.dest('public/dist/css'));
});

gulp.task('default', ['sass', 'img'])

gulp.task('watch', function() {
  gulp.watch('ui/sass/**/*.scss', ['sass']);
  gulp.watch('ui/img/**/*', ['img']);
});
