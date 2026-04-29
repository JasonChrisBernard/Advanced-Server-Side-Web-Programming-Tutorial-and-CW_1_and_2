// Import the shared SQLite database connection used by the rest of the app.
const db = require("../config/db");

// Read one alumni profile row by the logged-in user's id.
function getProfileRowByUserId(userId) {
  return db
    .prepare("SELECT * FROM alumni_profiles WHERE user_id = ?")
    .get(userId);
}

// Read all rows for a repeatable profile section such as degrees or courses.
function getSectionRows(tableName, profileId) {
  return db
    .prepare(
      `SELECT * FROM ${tableName} WHERE profile_id = ? ORDER BY display_order ASC, id ASC`
    )
    .all(profileId);
}

// Delete and fully replace all rows in one repeatable profile section.
function replaceSectionRows(tableName, columns, profileId, rows) {
  // Remove the old rows first so the saved data always matches the latest form submit.
  db.prepare(`DELETE FROM ${tableName} WHERE profile_id = ?`).run(profileId);

  // If the user submitted no rows for this section, there is nothing else to insert.
  if (!rows.length) {
    return;
  }

  // Build the INSERT statement dynamically from the provided column list.
  const placeholders = columns.map(() => "?").join(", ");
  const stmt = db.prepare(`
    INSERT INTO ${tableName} (profile_id, ${columns.join(", ")}, display_order)
    VALUES (?, ${placeholders}, ?)
  `);

  // Insert each submitted row in the same order it appeared on the form.
  rows.forEach((row, index) => {
    const values = columns.map((column) => row[column] ?? null);
    stmt.run(profileId, ...values, index);
  });
}

// Save the full alumni profile and all child sections inside one database transaction.
const saveProfile = db.transaction((userId, profileData) => {
  // Use one timestamp for all updates in this save operation.
  const now = new Date().toISOString();
  // Check whether this user already has a profile row.
  const existingProfile = getProfileRowByUserId(userId);
  let profileId = existingProfile ? existingProfile.id : null;

  if (existingProfile) {
    // Update the existing main profile row when the profile already exists.
    db.prepare(`
      UPDATE alumni_profiles
      SET contact_number = ?,
          location = ?,
          professional_headline = ?,
          biography = ?,
          linkedin_url = ?,
          profile_image_path = ?,
          updated_at = ?
      WHERE id = ?
    `).run(
      profileData.contactNumber,
      profileData.location,
      profileData.professionalHeadline,
      profileData.biography,
      profileData.linkedinUrl,
      profileData.profileImagePath,
      now,
      profileId
    );
  } else {
    // Insert the main profile row for first-time profile creation.
    const info = db.prepare(`
      INSERT INTO alumni_profiles (
        user_id,
        contact_number,
        location,
        professional_headline,
        biography,
        linkedin_url,
        profile_image_path,
        created_at,
        updated_at
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `).run(
      userId,
      profileData.contactNumber,
      profileData.location,
      profileData.professionalHeadline,
      profileData.biography,
      profileData.linkedinUrl,
      profileData.profileImagePath,
      now,
      now
    );

    // Capture the new profile id so child tables can use it as a foreign key.
    profileId = Number(info.lastInsertRowid);
  }

  // Replace each repeatable section with the latest submitted values.
  replaceSectionRows(
    "alumni_profile_degrees",
    ["title", "institution_name", "official_url", "completion_date"],
    profileId,
    profileData.degrees
  );
  replaceSectionRows(
    "alumni_profile_certifications",
    ["title", "provider_name", "official_url", "completion_date"],
    profileId,
    profileData.certifications
  );
  replaceSectionRows(
    "alumni_profile_licenses",
    ["title", "awarding_body", "official_url", "completion_date"],
    profileId,
    profileData.licenses
  );
  replaceSectionRows(
    "alumni_profile_courses",
    ["title", "provider_name", "official_url", "completion_date"],
    profileId,
    profileData.courses
  );
  replaceSectionRows(
    "alumni_profile_employment",
    ["employer_name", "job_title", "start_date", "end_date"],
    profileId,
    profileData.employmentHistory
  );

  // Return the profile id so callers can redirect to the saved profile.
  return profileId;
});

// Delete the full alumni profile and all of its child rows inside one transaction.
const deleteProfileByUserId = db.transaction((userId) => {
  const existingProfile = getProfileRowByUserId(userId);

  if (!existingProfile) {
    return false;
  }

  db.prepare("DELETE FROM alumni_profile_degrees WHERE profile_id = ?").run(existingProfile.id);
  db.prepare("DELETE FROM alumni_profile_certifications WHERE profile_id = ?").run(existingProfile.id);
  db.prepare("DELETE FROM alumni_profile_licenses WHERE profile_id = ?").run(existingProfile.id);
  db.prepare("DELETE FROM alumni_profile_courses WHERE profile_id = ?").run(existingProfile.id);
  db.prepare("DELETE FROM alumni_profile_employment WHERE profile_id = ?").run(existingProfile.id);
  db.prepare("DELETE FROM alumni_profiles WHERE id = ?").run(existingProfile.id);

  return true;
});

// Read the complete alumni profile view model for one user.
exports.getProfileByUserId = (userId) => {
  // Read the main profile row first.
  const profile = getProfileRowByUserId(userId);

  // If no profile exists yet, return null so the controller can show the create page.
  if (!profile) {
    return null;
  }

  // Attach all repeatable child sections to the returned profile object.
  return {
    ...profile,
    degrees: getSectionRows("alumni_profile_degrees", profile.id),
    certifications: getSectionRows("alumni_profile_certifications", profile.id),
    licenses: getSectionRows("alumni_profile_licenses", profile.id),
    courses: getSectionRows("alumni_profile_courses", profile.id),
    employmentHistory: getSectionRows("alumni_profile_employment", profile.id),
  };
};

// Export the profile save function so the controller can write the whole profile.
exports.saveProfile = saveProfile;
// Export the delete helper so the controller can remove a complete profile cleanly.
exports.deleteProfileByUserId = deleteProfileByUserId;
