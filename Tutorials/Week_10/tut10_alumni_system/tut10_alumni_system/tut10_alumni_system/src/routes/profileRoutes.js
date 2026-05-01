const express = require('express');
const profileController = require('../controllers/profileController');
const { requireAuth } = require('../middleware/authMiddleware');
const { uploadProfileImage } = require('../middleware/upload');

const router = express.Router();

router.use(requireAuth);
router.get('/', profileController.showProfile);
router.get('/edit', profileController.renderEditProfile);
router.post('/edit', profileController.updateProfile);
router.post('/image', uploadProfileImage.single('profile_image'), profileController.uploadImage);

router.get('/items/new', profileController.renderNewItem);
router.post('/items', profileController.createItem);
router.get('/items/:id/edit', profileController.renderEditItem);
router.post('/items/:id/update', profileController.updateItem);
router.post('/items/:id/delete', profileController.deleteItem);

router.get('/employment/new', profileController.renderNewEmployment);
router.post('/employment', profileController.createEmployment);
router.get('/employment/:id/edit', profileController.renderEditEmployment);
router.post('/employment/:id/update', profileController.updateEmployment);
router.post('/employment/:id/delete', profileController.deleteEmployment);

module.exports = router;
